<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     */
    public function index()
    {
        $projects = Project::withCount('backlinks')
            ->latest()
            ->paginate(15);

        return view('pages.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('pages.projects.create');
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['user_id'] = auth()->id() ?? 1;

        $project = Project::create($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project, Request $request)
    {
        $project->loadCount('backlinks');

        // Stats avancées pour le pilotage (sur tous les backlinks)
        $allBacklinks = $project->backlinks()->get();

        $stats = [
            'total'              => $allBacklinks->count(),
            'active'             => $allBacklinks->where('status', 'active')->count(),
            'lost'               => $allBacklinks->where('status', 'lost')->count(),
            'changed'            => $allBacklinks->where('status', 'changed')->count(),
            'pending'            => $allBacklinks->where('status', 'pending')->count(),
            'quality'            => $allBacklinks->whereStrict('status', 'active')->whereStrict('is_indexed', true)->whereStrict('is_dofollow', true)->count(),
            'not_indexed'        => $allBacklinks->whereStrict('is_indexed', false)->count(),
            'not_dofollow'       => $allBacklinks->whereStrict('status', 'active')->whereStrict('http_status', 200)->whereStrict('is_dofollow', false)->count(),
            'unknown_indexed'    => $allBacklinks->whereNull('is_indexed')->count(),
            'pending_indexation' => $allBacklinks->whereIn('status', ['active', 'changed'])->whereNull('is_indexed')->count(),
            'budget_total'       => $allBacklinks->sum('price'),
            'budget_active'      => $allBacklinks->whereStrict('status', 'active')->sum('price'),
        ];

        // Tableau backlinks filtrable + paginé (même logique que BacklinkController@index)
        $validated = $request->validate([
            'search'      => 'nullable|string|max:255',
            'status'      => 'nullable|in:active,lost,changed,pending',
            'tier_level'  => 'nullable|in:tier1,tier2',
            'spot_type'   => 'nullable|in:external,internal',
            'http_status' => 'nullable|string|max:10',
            'is_dofollow' => 'nullable|in:1,0',
            'is_indexed'  => 'nullable|in:1,0,null',
            'sort'        => 'nullable|in:created_at,source_url,status,tier_level,spot_type,last_checked_at,http_status,is_dofollow,is_indexed',
            'direction'   => 'nullable|in:asc,desc',
            'per_page'    => 'nullable|integer|in:15,25,50,100',
        ]);

        $query = $project->backlinks()->with('project');

        if (!empty($validated['search'])) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('source_url', 'like', "%{$search}%")
                  ->orWhere('anchor_text', 'like', "%{$search}%")
                  ->orWhere('target_url', 'like', "%{$search}%");
            });
        }
        if (!empty($validated['status']))     { $query->where('status', $validated['status']); }
        if (!empty($validated['tier_level'])) { $query->where('tier_level', $validated['tier_level']); }
        if (!empty($validated['spot_type']))  { $query->where('spot_type', $validated['spot_type']); }

        if (isset($validated['http_status']) && $validated['http_status'] !== '') {
            $query->where('http_status', (int) $validated['http_status']);
        }
        if (isset($validated['is_dofollow']) && $validated['is_dofollow'] !== '') {
            $query->where('is_dofollow', (bool) $validated['is_dofollow']);
        }
        if (isset($validated['is_indexed']) && $validated['is_indexed'] !== '') {
            if ($validated['is_indexed'] === 'null') {
                $query->whereNull('is_indexed');
            } else {
                $query->where('is_indexed', (bool) $validated['is_indexed']);
            }
        }

        $query->orderBy($validated['sort'] ?? 'created_at', $validated['direction'] ?? 'desc');

        $perPage = (int) ($validated['per_page'] ?? 15);
        $backlinks = $query->paginate($perPage)->withQueryString();

        $activeFiltersCount = collect(['search', 'status', 'tier_level', 'spot_type', 'http_status', 'is_dofollow', 'is_indexed'])
            ->filter(fn($f) => isset($validated[$f]) && $validated[$f] !== '')
            ->count();

        return view('pages.projects.show', compact('project', 'stats', 'backlinks', 'activeFiltersCount'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        return view('pages.projects.edit', compact('project'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $project->update($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet mis à jour avec succès.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet supprimé avec succès.');
    }

    /**
     * STORY-039 : Rapport HTML imprimable du projet
     */
    public function report(Project $project)
    {
        $project->load(['backlinks' => function ($query) {
            $query->with('checks')->orderBy('status')->orderBy('source_url');
        }]);

        $stats = [
            'total'   => $project->backlinks->count(),
            'active'  => $project->backlinks->where('status', 'active')->count(),
            'lost'    => $project->backlinks->where('status', 'lost')->count(),
            'changed' => $project->backlinks->where('status', 'changed')->count(),
        ];

        // Enrichir avec les métriques de domaine disponibles
        $domains = $project->backlinks->map(function ($backlink) {
            return \App\Models\DomainMetric::extractDomain($backlink->source_url);
        })->unique()->filter()->values();

        $domainMetrics = \App\Models\DomainMetric::whereIn('domain', $domains)
            ->get()
            ->keyBy('domain');

        $generatedAt = now();

        return view('pages.projects.report', compact('project', 'stats', 'domainMetrics', 'generatedAt'));
    }
}
