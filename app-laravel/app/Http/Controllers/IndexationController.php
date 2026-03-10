<?php

namespace App\Http\Controllers;

use App\Jobs\SubmitIndexationJob;
use App\Models\Backlink;
use App\Models\IndexationCampaign;
use App\Models\IndexationSubmission;
use App\Services\Indexation\ReindexingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexationController extends Controller
{
    /**
     * Page principale : sélection + campagnes + rapport.
     */
    public function index(Request $request)
    {
        $user    = auth()->user() ?? \App\Models\User::first();

        if (! $user) {
            return redirect('/dashboard')->with('error', 'Aucun utilisateur configuré.');
        }

        $service = new ReindexingService();

        // KPIs globaux pour le rapport
        $stats = $this->buildGlobalStats($user->id);

        // Backlinks candidats à la réindexation :
        // - Tier 1 uniquement
        // - HTTP 200 lors du dernier check
        // - Dofollow (is_dofollow = true)
        // - Non indexé ou indexation inconnue (is_indexed != true)
        // - Pas perdu (statut actif, modifié ou en attente)
        $candidates = Backlink::where('tier_level', 'tier1')
            ->whereIn('status', ['active', 'changed', 'pending'])
            ->where('http_status', 200)
            ->where('is_dofollow', true)
            ->where(fn ($q) => $q->whereNull('is_indexed')->orWhere('is_indexed', false))
            ->with('project')
            ->orderBy('status')
            ->orderBy('last_checked_at', 'desc')
            ->get();

        // Campagnes récentes paginées
        $campaigns = IndexationCampaign::forUser($user->id)
            ->withCount('submissions')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'campaigns_page');

        // URLs soumises 2+ fois sans jamais être indexées
        $stubbornUrls = $this->getStubbornUrls($user->id);

        // Statistiques par provider
        $providerStats = $this->getProviderStats($user->id);

        return view('pages.indexation.index', compact(
            'stats',
            'candidates',
            'campaigns',
            'stubbornUrls',
            'providerStats',
            'service',
        ));
    }

    /**
     * Crée une nouvelle campagne et dispatche le job de soumission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'nullable|string|max:255',
            'backlink_ids'     => 'required|array|min:1|max:500',
            'backlink_ids.*'   => 'integer|exists:backlinks,id',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $user      = auth()->user() ?? \App\Models\User::first();

        if (! $user) {
            return redirect('/dashboard')->with('error', 'Aucun utilisateur configuré.');
        }

        $service   = new ReindexingService();

        if (! $service->isConfigured()) {
            return back()->withErrors([
                'provider' => 'Aucun provider de réindexation configuré. Veuillez configurer votre clé API dans les paramètres.',
            ]);
        }

        // Déduplique les IDs pour éviter les soumissions doubles
        $backlinkIds = array_unique($validated['backlink_ids']);
        $backlinks   = Backlink::whereIn('id', $backlinkIds)->get();

        $campaign = DB::transaction(function () use ($validated, $backlinks, $user, $service) {
            $campaign = IndexationCampaign::create([
                'user_id'    => $user->id,
                'provider'   => $service->getProviderName(),
                'status'     => 'pending',
                'name'       => $validated['name'] ?? 'Campagne ' . now()->format('d/m/Y H:i'),
                'total_urls' => $backlinks->count(),
                'notes'      => $validated['notes'] ?? null,
            ]);

            foreach ($backlinks as $backlink) {
                IndexationSubmission::create([
                    'campaign_id'       => $campaign->id,
                    'backlink_id'       => $backlink->id,
                    'source_url'        => $backlink->source_url,
                    'submission_status' => 'pending',
                ]);
            }

            return $campaign;
        });

        SubmitIndexationJob::dispatch($campaign);

        return redirect()
            ->route('indexation.campaigns.show', $campaign)
            ->with('success', "{$backlinks->count()} URL(s) soumises pour réindexation via {$service->getProviderName()}.");
    }

    /**
     * Détail d'une campagne avec ses soumissions.
     */
    public function showCampaign(IndexationCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        $submissions = $campaign->submissions()
            ->with('backlink.project')
            ->orderByRaw("FIELD(submission_status, 'indexed', 'submitted', 'not_indexed', 'submit_error', 'check_error', 'pending')")
            ->paginate(50);

        return view('pages.indexation.campaign', compact('campaign', 'submissions'));
    }

    /**
     * Endpoint JSON pour le polling AlpineJS du statut d'une campagne.
     */
    public function campaignStatus(IndexationCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        return response()->json([
            'status'          => $campaign->status,
            'submitted_count' => $campaign->submitted_count,
            'indexed_count'   => $campaign->indexed_count,
            'failed_count'    => $campaign->failed_count,
            'total_urls'      => $campaign->total_urls,
        ]);
    }

    private function buildGlobalStats(int $userId): array
    {
        $totals = IndexationCampaign::forUser($userId)
            ->selectRaw('COUNT(*) as total_campaigns, SUM(submitted_count) as total_submitted, SUM(indexed_count) as total_indexed')
            ->first();

        $successRate = null;
        if ($totals && $totals->total_submitted > 0) {
            $successRate = round($totals->total_indexed / $totals->total_submitted * 100, 1);
        }

        return [
            'total_campaigns' => $totals->total_campaigns ?? 0,
            'total_submitted' => $totals->total_submitted ?? 0,
            'total_indexed'   => $totals->total_indexed ?? 0,
            'success_rate'    => $successRate,
        ];
    }

    private function getStubbornUrls(int $userId)
    {
        return IndexationSubmission::whereHas(
            'campaign',
            fn ($q) => $q->where('user_id', $userId)
        )
            ->where('submission_status', 'not_indexed')
            ->select('source_url', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('source_url')
            ->having('attempt_count', '>=', 2)
            ->orderByDesc('attempt_count')
            ->limit(20)
            ->get();
    }

    private function getProviderStats(int $userId)
    {
        return IndexationCampaign::forUser($userId)
            ->select(
                'provider',
                DB::raw('COUNT(*) as campaign_count'),
                DB::raw('SUM(submitted_count) as total_submitted'),
                DB::raw('SUM(indexed_count) as total_indexed')
            )
            ->groupBy('provider')
            ->get()
            ->map(function ($row) {
                $row->success_rate = $row->total_submitted > 0
                    ? round($row->total_indexed / $row->total_submitted * 100, 1)
                    : null;
                return $row;
            });
    }
}
