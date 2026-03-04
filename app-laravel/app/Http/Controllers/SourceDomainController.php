<?php

namespace App\Http\Controllers;

use App\Jobs\FetchSeoMetricsJob;
use App\Models\DomainMetric;
use App\Models\SourceDomain;
use Illuminate\Http\Request;

/**
 * STORY-065/066/067 : Gestion du catalogue de domaines sources.
 */
class SourceDomainController extends Controller
{
    /**
     * GET /domains — Liste des domaines sources avec filtres et métriques SEO.
     */
    public function index(Request $request)
    {
        $search   = $request->get('search', '');
        $daMin    = $request->get('da_min');
        $daMax    = $request->get('da_max');
        $spamMax  = $request->get('spam_max');
        $sort     = in_array($request->get('sort'), ['domain', 'da', 'dr', 'spam_score', 'referring_domains_count', 'first_seen_at', 'backlinks_count', 'projects_count']) ? $request->get('sort') : 'domain';
        $dir      = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = SourceDomain::query()
            ->leftJoin('domain_metrics', 'domain_metrics.domain', '=', 'source_domains.domain')
            ->select(
                'source_domains.*',
                'domain_metrics.da',
                'domain_metrics.dr',
                'domain_metrics.spam_score',
                'domain_metrics.referring_domains_count',
                'domain_metrics.last_updated_at as metrics_updated_at',
                \Illuminate\Support\Facades\DB::raw('(SELECT COUNT(*) FROM backlinks WHERE backlinks.source_url LIKE "%"||source_domains.domain||"%") as backlinks_count'),
                \Illuminate\Support\Facades\DB::raw('(SELECT COUNT(DISTINCT project_id) FROM backlinks WHERE backlinks.source_url LIKE "%"||source_domains.domain||"%") as projects_count'),
            );

        if ($search) {
            $query->where('source_domains.domain', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }

        if ($daMin !== null && $daMin !== '') {
            $query->where('domain_metrics.da', '>=', (int) $daMin);
        }

        if ($daMax !== null && $daMax !== '') {
            $query->where('domain_metrics.da', '<=', (int) $daMax);
        }

        if ($spamMax !== null && $spamMax !== '') {
            $query->where('domain_metrics.spam_score', '<=', (int) $spamMax);
        }

        // Tri
        $sortColumn = match ($sort) {
            'da', 'dr', 'spam_score', 'referring_domains_count' => "domain_metrics.{$sort}",
            'backlinks_count', 'projects_count'                  => $sort,
            'first_seen_at'                                      => "source_domains.first_seen_at",
            default                                              => "source_domains.{$sort}",
        };
        $query->orderByRaw("{$sortColumn} IS NULL ASC")->orderBy($sortColumn, $dir)->orderBy('source_domains.domain', 'asc');

        $domains = $query->paginate(25)->withQueryString();

        // Les counts sont maintenant des colonnes SQL : on construit les maps depuis les résultats
        $backlinkCountsMap = $domains->pluck('backlinks_count', 'domain')->toArray();
        $projectCountsMap  = $domains->pluck('projects_count', 'domain')->toArray();

        return view('pages.domains.index', compact(
            'domains',
            'backlinkCountsMap',
            'projectCountsMap',
            'search',
            'daMin',
            'daMax',
            'spamMax',
            'sort',
            'dir'
        ));
    }

    /**
     * GET /domains/{domain} — Page détail d'un domaine source.
     */
    public function show(string $domain)
    {
        $sourceDomain = SourceDomain::where('domain', $domain)
            ->with('domainMetric')
            ->firstOrFail();

        // Tous les backlinks du domaine, groupés par projet
        $allBacklinks = \App\Models\Backlink::with(['project', 'platform'])
            ->where('source_url', 'like', '%' . $domain . '%')
            ->orderByDesc('published_at')
            ->get();

        $allProjects = \App\Models\Project::orderBy('name')->get();
        $linkedProjectIds = $allBacklinks->pluck('project_id')->unique();

        // Projets liés : avec leurs backlinks groupés
        $linkedProjects = $allProjects
            ->filter(fn($p) => $linkedProjectIds->contains($p->id))
            ->map(function ($project) use ($allBacklinks) {
                $bl = $allBacklinks->where('project_id', $project->id)->values();
                return [
                    'project'     => $project,
                    'backlinks'   => $bl,
                    'count'       => $bl->count(),
                    'active'      => $bl->where('status', 'active')->count(),
                    'lost'        => $bl->where('status', 'lost')->count(),
                    'price_total' => $bl->sum('price'),
                    'price_trend' => $this->computePriceTrend($bl),
                    'last_date'   => $bl->max('published_at'),
                ];
            })->values();

        // Projets non liés = opportunités
        $unlinkedProjects = $allProjects
            ->filter(fn($p) => !$linkedProjectIds->contains($p->id))
            ->values();

        // Indicateur prix global du domaine
        $priceTrend = $this->computePriceTrendFromDomain($domain);

        // "Mon site" = ce domaine correspond à l'URL d'un de mes projets (c'est MON site)
        // Différent de "j'y ai acheté des liens" (= domaine source externe)
        $isInPortfolio = $allProjects->contains(
            fn($p) => $p->url && str_contains(rtrim($p->url, '/'), $domain)
        );

        return view('pages.domains.show', compact(
            'sourceDomain',
            'linkedProjects',
            'unlinkedProjects',
            'priceTrend',
            'isInPortfolio'
        ));
    }

    /**
     * POST /domains — Ajout manuel d'un domaine.
     */
    public function store(Request $request)
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z]{2,})+$/i'],
        ]);

        $normalized = DomainMetric::extractDomain('https://' . $request->domain);

        $sourceDomain = SourceDomain::firstOrCreate(
            ['domain' => $normalized],
            ['first_seen_at' => now()]
        );

        if ($sourceDomain->wasRecentlyCreated) {
            $metric = DomainMetric::forDomain($normalized);
            FetchSeoMetricsJob::dispatch($metric);
            return redirect()->route('domains.show', $normalized)
                ->with('success', "Domaine {$normalized} ajouté. Les métriques SEO seront calculées sous peu.");
        }

        return redirect()->route('domains.show', $normalized)
            ->with('info', "Le domaine {$normalized} existe déjà dans votre catalogue.");
    }

    /**
     * POST /domains/{domain}/refresh-metrics — Rafraîchit les métriques SEO.
     */
    public function refreshMetrics(string $domain)
    {
        $sourceDomain = SourceDomain::where('domain', $domain)->firstOrFail();
        $metric = DomainMetric::forDomain($sourceDomain->domain);
        FetchSeoMetricsJob::dispatch($metric);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'Rafraîchissement des métriques lancé en arrière-plan.');
    }

    /**
     * Calcule l'indicateur de tendance de prix pour une collection de backlinks.
     */
    private function computePriceTrend(\Illuminate\Support\Collection $backlinks): array
    {
        $withPrice = $backlinks->whereNotNull('price')->sortByDesc('published_at')->values();

        if ($withPrice->count() < 1) {
            return ['label' => null, 'type' => null];
        }

        if ($withPrice->count() === 1) {
            return ['label' => 'Premier achat', 'type' => 'first'];
        }

        $last   = (float) $withPrice->get(0)->price;
        $before = (float) $withPrice->get(1)->price;

        if ($last > $before) {
            return ['label' => '↑ Plus cher', 'type' => 'up'];
        } elseif ($last < $before) {
            return ['label' => '↓ Moins cher', 'type' => 'down'];
        }

        return ['label' => '= Stable', 'type' => 'stable'];
    }

    private function computePriceTrendFromDomain(string $domain): array
    {
        $backlinks = \App\Models\Backlink::where('source_url', 'like', '%' . $domain . '%')
            ->whereNotNull('price')
            ->orderByDesc('published_at')
            ->take(2)
            ->get();

        return $this->computePriceTrend($backlinks);
    }
}
