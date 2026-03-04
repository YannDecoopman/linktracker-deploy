<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\BacklinkCheck;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Statistiques mises en cache 5 minutes
        $stats = Cache::remember('dashboard_stats', 300, function () {
            $activeBacklinks  = Backlink::where('status', 'active')->count();
            $lostBacklinks    = Backlink::where('status', 'lost')->count();
            $changedBacklinks = Backlink::where('status', 'changed')->count();
            $totalBacklinks   = Backlink::count();
            $totalProjects    = Project::count();

            $totalChecks   = BacklinkCheck::where('checked_at', '>=', now()->subDays(30))->count();
            $presentChecks = BacklinkCheck::where('checked_at', '>=', now()->subDays(30))->where('is_present', true)->count();
            $uptimeRate    = $totalChecks > 0 ? round(($presentChecks / $totalChecks) * 100, 1) : null;

            // Stats avancées (pilotage)
            $qualityLinks    = Backlink::where('status', 'active')->where('is_indexed', true)->where('is_dofollow', true)->count();
            $notIndexed      = Backlink::where('is_indexed', false)->count();
            $notDofollow     = Backlink::where('is_dofollow', false)->count();
            $unknownIndexed  = Backlink::whereNull('is_indexed')->count();
            $budgetTotal     = Backlink::sum('price');
            $budgetActive    = Backlink::where('status', 'active')->sum('price');

            $healthScore = $totalBacklinks > 0 ? (int) round(
                ($activeBacklinks / $totalBacklinks) * 60 +
                ($totalBacklinks - $unknownIndexed > 0
                    ? ($qualityLinks / max(1, $totalBacklinks - $unknownIndexed)) * 40
                    : 0)
            ) : 0;

            return compact(
                'activeBacklinks', 'lostBacklinks', 'changedBacklinks',
                'totalBacklinks', 'totalProjects',
                'totalChecks', 'uptimeRate',
                'qualityLinks', 'notIndexed', 'notDofollow', 'unknownIndexed',
                'budgetTotal', 'budgetActive', 'healthScore'
            );
        });

        // Données fraîches (pas de cache) : projets récents, backlinks récents, alertes
        $recentProjects = Project::withCount('backlinks')
            ->latest()
            ->take(5)
            ->get();

        $recentBacklinks = Backlink::with('project')
            ->latest()
            ->take(10)
            ->get();

        $recentAlerts = Alert::with('backlink.project')
            ->latest()
            ->take(5)
            ->get();

        return view('pages.dashboard', array_merge($stats, compact(
            'recentProjects',
            'recentBacklinks',
            'recentAlerts'
        )));
    }

    /**
     * Retourne les données du graphique d'évolution (JSON pour Chart.js).
     * Basé sur les snapshots quotidiens pour une mesure exacte à chaque date.
     * GET /api/dashboard/chart?days=30&project_id=
     */
    public function chartData(Request $request)
    {
        $days      = (int) $request->get('days', 30);
        $projectId = $request->get('project_id') ?: null;

        $days = in_array($days, [7, 30, 90, 180, 365]) ? $days : 30;

        $cacheKey = "dashboard_chart_v2_" . ($projectId ?? 'all') . "_{$days}";

        $data = Cache::remember($cacheKey, 300, function () use ($days, $projectId) {

            $startDate = today()->subDays($days - 1);

            // Générer toutes les dates de la fenêtre
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dates[] = today()->subDays($i)->toDateString();
            }

            // Charger les snapshots de la période
            $snapshots = BacklinkSnapshot::query()
                ->when($projectId, fn($q) => $q->where('project_id', $projectId), fn($q) => $q->whereNull('project_id'))
                ->whereBetween(DB::raw("DATE(snapshot_date)"), [$startDate->toDateString(), today()->toDateString()])
                ->orderBy('snapshot_date')
                ->get()
                ->keyBy(fn($s) => $s->snapshot_date->toDateString());

            // Trouver le dernier snapshot avant la fenêtre pour interpoler les jours sans données
            $lastKnown = BacklinkSnapshot::query()
                ->when($projectId, fn($q) => $q->where('project_id', $projectId), fn($q) => $q->whereNull('project_id'))
                ->where(DB::raw("DATE(snapshot_date)"), '<', $startDate->toDateString())
                ->orderByDesc('snapshot_date')
                ->first();

            // Construire les séries en propageant la dernière valeur connue sur les jours sans snapshot
            $active      = [];
            $lost        = [];
            $changed     = [];
            $total       = [];
            $perfect     = [];
            $notIndexed  = [];
            $nofollow    = [];
            $gained      = [];
            $lostDelta   = [];
            $delta       = [];

            $prevTotal      = $lastKnown?->count_total       ?? 0;
            $prevActive     = $lastKnown?->count_active      ?? 0;
            $prevLost       = $lastKnown?->count_lost        ?? 0;
            $prevChanged    = $lastKnown?->count_changed     ?? 0;
            $prevPerfect    = $lastKnown?->count_perfect     ?? 0;
            $prevNotIndexed = $lastKnown?->count_not_indexed ?? 0;
            $prevNofollow   = $lastKnown?->count_nofollow    ?? 0;

            foreach ($dates as $date) {
                $snap = $snapshots[$date] ?? null;

                $activeVal      = $snap ? $snap->count_active      : $prevActive;
                $lostVal        = $snap ? $snap->count_lost        : $prevLost;
                $changedVal     = $snap ? $snap->count_changed     : $prevChanged;
                $totalVal       = $snap ? $snap->count_total       : $prevTotal;
                $perfectVal     = $snap ? $snap->count_perfect     : $prevPerfect;
                $notIndexedVal  = $snap ? $snap->count_not_indexed : $prevNotIndexed;
                $nofollowVal    = $snap ? $snap->count_nofollow    : $prevNofollow;

                // Gains/pertes journaliers = delta par rapport au jour précédent
                $gainedVal  = max(0, $totalVal - $prevTotal);
                $lostDayVal = max(0, $lostVal  - $prevLost);

                $active[]     = $activeVal;
                $lost[]       = $lostVal;
                $changed[]    = $changedVal;
                $total[]      = $totalVal;
                $perfect[]    = $perfectVal;
                $notIndexed[] = $notIndexedVal;
                $nofollow[]   = $nofollowVal;
                $gained[]     = $gainedVal;
                $lostDelta[]  = $lostDayVal;
                $delta[]      = $totalVal - $prevTotal;

                if ($snap) {
                    $prevTotal      = $totalVal;
                    $prevActive     = $activeVal;
                    $prevLost       = $lostVal;
                    $prevChanged    = $changedVal;
                    $prevPerfect    = $perfectVal;
                    $prevNotIndexed = $notIndexedVal;
                    $prevNofollow   = $nofollowVal;
                }
            }

            $labelFormat = $days <= 90 ? 'd/m' : 'd/m/y';

            return [
                'labels'      => array_map(fn($d) => \Carbon\Carbon::parse($d)->format($labelFormat), $dates),
                'active'      => $active,
                'lost'        => $lost,
                'changed'     => $changed,
                'total'       => $total,
                'perfect'     => $perfect,
                'not_indexed' => $notIndexed,
                'nofollow'    => $nofollow,
                'gained'      => $gained,
                'lostDelta'   => $lostDelta,
                'delta'       => $delta,
            ];
        });

        return response()->json($data);
    }
}
