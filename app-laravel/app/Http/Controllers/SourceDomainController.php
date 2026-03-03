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
        $sort     = in_array($request->get('sort'), ['domain', 'da', 'dr', 'spam_score', 'backlinks_count', 'first_seen_at']) ? $request->get('sort') : 'domain';
        $dir      = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = SourceDomain::with('domainMetric')
            ->withCount(['domainMetric as backlinks_total' => function ($q) {
                // Nombre de backlinks via jointure SQL directe
            }]);

        // On va faire une requête avec JOIN pour les counts et le tri par métriques
        $query = SourceDomain::query()
            ->leftJoin('domain_metrics', 'domain_metrics.domain', '=', 'source_domains.domain')
            ->select(
                'source_domains.*',
                'domain_metrics.da',
                'domain_metrics.dr',
                'domain_metrics.spam_score',
                'domain_metrics.referring_domains_count',
                'domain_metrics.last_updated_at as metrics_updated_at',
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
            default => "source_domains.{$sort}",
        };
        $query->orderBy($sortColumn, $dir)->orderBy('source_domains.domain', 'asc');

        $domains = $query->paginate(25)->withQueryString();

        // Injecter le nombre de backlinks et de projets distincts pour chaque domaine
        $domainNames = $domains->pluck('domain')->toArray();

        $backlinkCounts = \App\Models\Backlink::selectRaw('LOWER(REPLACE(REPLACE(REPLACE(source_url, "https://www.", ""), "http://www.", ""), "https://", "")) as clean_domain, COUNT(*) as cnt')
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(REPLACE(REPLACE(REPLACE(source_url, "https://www.", ""), "http://www.", ""), "https://", ""))'), array_map(fn($d) => $d . '/', $domainNames))
            ->groupBy('clean_domain')
            ->pluck('cnt', 'clean_domain');

        // Calcul simplifié : requête par domaine
        $backlinkCountsMap = [];
        $projectCountsMap  = [];

        foreach ($domainNames as $domain) {
            $bl = \App\Models\Backlink::where('source_url', 'like', '%' . $domain . '%')->get();
            $backlinkCountsMap[$domain] = $bl->count();
            $projectCountsMap[$domain]  = $bl->pluck('project_id')->unique()->count();
        }

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

        // Backlinks achetés sur ce domaine
        $backlinks = \App\Models\Backlink::with(['project', 'platform'])
            ->where('source_url', 'like', '%' . $domain . '%')
            ->orderByDesc('published_at')
            ->paginate(20);

        // Matrice couverture projets
        $allProjects = \App\Models\Project::orderBy('name')->get();
        $linkedProjectIds = \App\Models\Backlink::where('source_url', 'like', '%' . $domain . '%')
            ->pluck('project_id')
            ->unique();

        $projectCoverage = $allProjects->map(function ($project) use ($domain, $linkedProjectIds) {
            $bl = \App\Models\Backlink::where('project_id', $project->id)
                ->where('source_url', 'like', '%' . $domain . '%')
                ->orderByDesc('published_at')
                ->get();

            return [
                'project'       => $project,
                'linked'        => $linkedProjectIds->contains($project->id),
                'count'         => $bl->count(),
                'last_backlink' => $bl->first(),
                'price_trend'   => $this->computePriceTrend($bl),
            ];
        });

        // Indicateur prix global du domaine
        $priceTrend = $this->computePriceTrendFromDomain($domain);

        return view('pages.domains.show', compact(
            'sourceDomain',
            'backlinks',
            'projectCoverage',
            'priceTrend'
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
