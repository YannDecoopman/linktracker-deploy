<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\Project;
use App\Services\Alert\AlertService;
use App\Services\Backlink\BacklinkCheckerService;
use App\Services\Security\UrlValidator;
use Illuminate\Http\Request;

class BacklinkController extends Controller
{
    /**
     * Display a listing of all backlinks (global view).
     */
    public function index(Request $request)
    {
        // Validation des paramètres de filtrage
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,lost,changed',
            'project_id' => 'nullable|integer|exists:projects,id',
            'tier_level' => 'nullable|in:tier1,tier2',
            'spot_type' => 'nullable|in:external,internal',
            'sort' => 'nullable|in:created_at,source_url,status,tier_level,spot_type,last_checked_at',
            'direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:15,25,50,100',
        ]);

        $query = Backlink::with('project');

        // Filtrer par recherche textuelle (avec échappement des wildcards SQL)
        if (!empty($validated['search'])) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $validated['search']);
            $query->where(function($q) use ($search) {
                $q->where('source_url', 'like', "%{$search}%")
                  ->orWhere('anchor_text', 'like', "%{$search}%")
                  ->orWhere('target_url', 'like', "%{$search}%");
            });
        }

        // Filtrer par status
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filtrer par projet
        if (!empty($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }

        // Filtrer par tier level
        if (!empty($validated['tier_level'])) {
            $query->where('tier_level', $validated['tier_level']);
        }

        // Filtrer par spot type
        if (!empty($validated['spot_type'])) {
            $query->where('spot_type', $validated['spot_type']);
        }

        // Tri (déjà validé par la validation)
        $sortField = $validated['sort'] ?? 'created_at';
        $sortDirection = $validated['direction'] ?? 'desc';

        $query->orderBy($sortField, $sortDirection);

        // Pagination (15 items par page)
        $perPage = (int) ($validated['per_page'] ?? 15);
        $backlinks = $query->paginate($perPage)->withQueryString();

        // Charger tous les projets pour le filtre
        $projects = Project::orderBy('name')->get();

        // Compter les filtres actifs
        $activeFiltersCount = collect(['search', 'status', 'project_id', 'tier_level', 'spot_type'])
            ->filter(fn($filter) => !empty($validated[$filter]))
            ->count();

        return view('pages.backlinks.index', compact('backlinks', 'projects', 'activeFiltersCount'));
    }

    /**
     * Show the form for creating a new backlink.
     */
    public function create(Request $request)
    {
        $projects = Project::all();
        $platforms = \App\Models\Platform::active()->orderBy('name')->get();
        $tier1Backlinks = Backlink::where('tier_level', 'tier1')
            ->with('project')
            ->orderBy('source_url')
            ->get();
        $selectedProjectId = $request->query('project_id');

        return view('pages.backlinks.create', compact('projects', 'platforms', 'tier1Backlinks', 'selectedProjectId'));
    }

    /**
     * Store a newly created backlink in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Champs existants
            'project_id' => 'required|exists:projects,id',
            'source_url' => [
                'required',
                'url',
                'max:500',
                \Illuminate\Validation\Rule::unique('backlinks', 'source_url')->where('project_id', $request->project_id),
                function ($attribute, $value, $fail) {
                    try {
                        app(UrlValidator::class)->validate($value);
                    } catch (\App\Exceptions\SsrfException $e) {
                        $fail("L'URL source est bloquée pour des raisons de sécurité : " . $e->getMessage());
                    }
                },
            ],
            'target_url' => 'required|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'rel_attributes' => 'nullable|string|max:100',
            'is_dofollow' => 'nullable|boolean',
            'status' => 'nullable|in:active', // Only 'active' allowed on creation

            // Champs extended
            'tier_level' => 'required|in:tier1,tier2',
            'parent_backlink_id' => [
                'required_if:tier_level,tier2',
                'nullable',
                'exists:backlinks,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
                        $parentBacklink = Backlink::find($value);
                        if ($parentBacklink && $parentBacklink->tier_level !== 'tier1') {
                            $fail('Le lien parent doit être un lien Tier 1.');
                        }

                        if ($request->tier_level === 'tier1') {
                            $fail('Un lien Tier 1 ne peut pas avoir de lien parent.');
                        }
                    }
                },
            ],
            'spot_type' => 'required|in:external,internal',
            'published_at' => 'nullable|date',
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:published_at',
            ],
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => [
                'required_with:price',
                'nullable',
                'string',
                'size:3',
                'in:EUR,USD,GBP,CAD,BRL,MXN,ARS,COP,CLP,PEN',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && !$request->filled('price')) {
                        $fail('Le prix est requis lorsqu\'une devise est sélectionnée.');
                    }
                },
            ],
            'invoice_paid' => 'nullable|boolean',
            'platform_id' => 'nullable|exists:platforms,id',
            'contact_info' => 'nullable|string|max:1000',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
        ]);

        // Use DB transaction for data integrity
        $backlink = \DB::transaction(function () use ($validated, $request) {
            // Handle checkbox values (convert to boolean)
            $validated['invoice_paid'] = $request->boolean('invoice_paid');

            // Set default values
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['first_seen_at'] = now();

            // Ajouter l'utilisateur connecté si disponible
            if (auth()->check()) {
                $validated['created_by_user_id'] = auth()->id();
            }

            return Backlink::create($validated);
        });

        return redirect()
            ->route('backlinks.index')
            ->with('success', 'Backlink créé avec succès.');
    }

    /**
     * Display the specified backlink.
     */
    public function show(Backlink $backlink)
    {
        $backlink->load(['project', 'checks']);

        $domain = \App\Models\DomainMetric::extractDomain($backlink->source_url);
        $domainMetric = \App\Models\DomainMetric::where('domain', $domain)->first();

        return view('pages.backlinks.show', compact('backlink', 'domainMetric'));
    }

    /**
     * Déclenche une récupération des métriques SEO pour le domaine source du backlink.
     */
    public function refreshSeoMetrics(Backlink $backlink)
    {
        $domain = \App\Models\DomainMetric::extractDomain($backlink->source_url);
        $record = \App\Models\DomainMetric::forDomain($domain);

        \App\Jobs\FetchSeoMetricsJob::dispatch($record);

        return response()->json([
            'success' => true,
            'message' => "Récupération des métriques lancée pour {$domain}.",
        ]);
    }

    /**
     * Show the form for editing the specified backlink.
     */
    public function edit(Backlink $backlink)
    {
        $projects = Project::all();
        $platforms = \App\Models\Platform::active()->orderBy('name')->get();
        $tier1Backlinks = Backlink::where('tier_level', 'tier1')
            ->where('id', '!=', $backlink->id) // Exclure le backlink en cours d'édition
            ->with('project')
            ->orderBy('source_url')
            ->get();

        return view('pages.backlinks.edit', compact('backlink', 'projects', 'platforms', 'tier1Backlinks'));
    }

    /**
     * Update the specified backlink in storage.
     */
    public function update(Request $request, Backlink $backlink)
    {
        $validated = $request->validate([
            // Champs existants
            'project_id' => 'required|exists:projects,id',
            'source_url' => [
                'required',
                'url',
                'max:500',
                \Illuminate\Validation\Rule::unique('backlinks', 'source_url')->where('project_id', $request->project_id)->ignore($backlink->id),
                function ($attribute, $value, $fail) {
                    try {
                        app(UrlValidator::class)->validate($value);
                    } catch (\App\Exceptions\SsrfException $e) {
                        $fail("L'URL source est bloquée pour des raisons de sécurité : " . $e->getMessage());
                    }
                },
            ],
            'target_url' => 'required|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'rel_attributes' => 'nullable|string|max:100',
            'is_dofollow' => 'nullable|boolean',
            'status' => 'nullable|in:active,lost,changed',

            // Champs extended
            'tier_level' => 'required|in:tier1,tier2',
            'parent_backlink_id' => [
                'required_if:tier_level,tier2',
                'nullable',
                'exists:backlinks,id',
                function ($attribute, $value, $fail) use ($request, $backlink) {
                    if ($value) {
                        $parentBacklink = Backlink::find($value);
                        if ($parentBacklink && $parentBacklink->tier_level !== 'tier1') {
                            $fail('Le lien parent doit être un lien Tier 1.');
                        }

                        if ($request->tier_level === 'tier1') {
                            $fail('Un lien Tier 1 ne peut pas avoir de lien parent.');
                        }

                        if ($value == $backlink->id) {
                            $fail('Un backlink ne peut pas être son propre parent.');
                        }

                        if ($backlink->childBacklinks()->exists() && $request->tier_level === 'tier2') {
                            $fail('Ce backlink ne peut pas devenir Tier 2 car il a déjà des backlinks enfants.');
                        }

                        if ($parentBacklink && $parentBacklink->parent_backlink_id == $backlink->id) {
                            $fail('Référence circulaire détectée : le lien parent sélectionné pointe déjà vers ce backlink.');
                        }
                    }
                },
            ],
            'spot_type' => 'required|in:external,internal',
            'published_at' => 'nullable|date',
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:published_at',
            ],
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => [
                'required_with:price',
                'nullable',
                'string',
                'size:3',
                'in:EUR,USD,GBP,CAD,BRL,MXN,ARS,COP,CLP,PEN',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && !$request->filled('price')) {
                        $fail('Le prix est requis lorsqu\'une devise est sélectionnée.');
                    }
                },
            ],
            'invoice_paid' => 'nullable|boolean',
            'platform_id' => 'nullable|exists:platforms,id',
            'contact_info' => 'nullable|string|max:1000',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
        ]);

        // Use DB transaction for data integrity
        \DB::transaction(function () use ($validated, $request, $backlink) {
            // Handle checkbox values (convert to boolean)
            $validated['invoice_paid'] = $request->boolean('invoice_paid');
            // Ne pas écraser is_dofollow : cette valeur est gérée exclusivement
            // par le checker (BacklinkCheckerService) lors des vérifications.
            unset($validated['is_dofollow']);

            $backlink->update($validated);
        });

        return redirect()
            ->route('backlinks.index')
            ->with('success', 'Backlink mis à jour avec succès.');
    }

    /**
     * Remove the specified backlink from storage.
     */
    public function destroy(Backlink $backlink)
    {
        $backlink->delete();

        return redirect()
            ->route('backlinks.index')
            ->with('success', 'Backlink supprimé avec succès.');
    }

    /**
     * Check a backlink manually (on-demand verification).
     * Accepts both classic form POST (redirects to show) and JSON fetch (returns JSON).
     */
    public function check(Request $request, Backlink $backlink, BacklinkCheckerService $checkerService, AlertService $alertService)
    {
        $wantsJson = $request->expectsJson() || $request->header('X-Requested-With') === 'fetch';

        try {
            $result = $checkerService->check($backlink);

            $backlink->checks()->create([
                'checked_at'    => now(),
                'is_present'    => $result['is_present'],
                'http_status'   => $result['http_status'],
                'error_message' => $result['error_message'],
            ]);

            $updateData = ['last_checked_at' => now()];
            $oldStatus  = $backlink->status;

            if ($result['is_present']) {
                if ($result['anchor_text'] !== null && $result['anchor_text'] !== $backlink->anchor_text) {
                    $updateData['anchor_text'] = $result['anchor_text'];
                }
                $updateData['rel_attributes'] = $result['rel_attributes'];
                $updateData['is_dofollow']    = $result['is_dofollow'];
                $updateData['http_status']    = $result['http_status'];

                if ($backlink->status === 'lost') {
                    $updateData['status'] = 'active';
                    $alertService->createBacklinkRecoveredAlert($backlink);
                } elseif ($backlink->status === 'active') {
                    $changes = $this->getAttributesChanges($backlink, $result);
                    if (!empty($changes)) {
                        $updateData['status'] = 'changed';
                        $alertService->createBacklinkChangedAlert($backlink, $changes);
                    }
                }
            } else {
                if ($backlink->status !== 'lost') {
                    $updateData['status'] = 'lost';
                    $alertService->createBacklinkLostAlert($backlink, $result['error_message']);
                }
            }

            $backlink->update($updateData);

            $newStatus     = $backlink->fresh()->status;
            $statusChanged = $oldStatus !== $newStatus;

            if ($wantsJson) {
                return response()->json([
                    'success'        => true,
                    'is_present'     => $result['is_present'],
                    'http_status'    => $result['http_status'],
                    'status'         => $newStatus,
                    'status_changed' => $statusChanged,
                    'old_status'     => $oldStatus,
                ]);
            }

            $message = $result['is_present']
                ? 'Backlink vérifié : le lien est présent et actif.'
                : 'Backlink vérifié : le lien n\'a pas été trouvé sur la page.';

            if ($statusChanged) {
                $message .= " Statut mis à jour : {$oldStatus} → {$newStatus}.";
            }

            return redirect()
                ->route('backlinks.show', $backlink)
                ->with($result['is_present'] ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            $backlink->checks()->create([
                'checked_at'    => now(),
                'is_present'    => false,
                'http_status'   => null,
                'error_message' => 'Manual check failed: ' . $e->getMessage(),
            ]);

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('backlinks.show', $backlink)
                ->with('error', 'Erreur lors de la vérification : ' . $e->getMessage());
        }
    }

    /**
     * Get the changes in backlink attributes
     *
     * @param Backlink $backlink
     * @param array $result
     * @return array
     */
    protected function getAttributesChanges(Backlink $backlink, array $result): array
    {
        $changes = [];

        if ($result['anchor_text'] !== null && $backlink->anchor_text !== $result['anchor_text']) {
            $changes['anchor_text'] = [
                'old' => $backlink->anchor_text,
                'new' => $result['anchor_text'],
            ];
        }

        if ($backlink->rel_attributes !== $result['rel_attributes']) {
            $changes['rel_attributes'] = [
                'old' => $backlink->rel_attributes,
                'new' => $result['rel_attributes'],
            ];
        }

        if ($backlink->is_dofollow !== $result['is_dofollow']) {
            $changes['is_dofollow'] = [
                'old' => $backlink->is_dofollow ? 'Oui' : 'Non',
                'new' => $result['is_dofollow'] ? 'Oui' : 'Non',
            ];
        }

        return $changes;
    }

    /**
     * Show the CSV import form. (STORY-031)
     */
    public function importForm()
    {
        $projects = Project::orderBy('name')->get();
        return view('pages.backlinks.import', compact('projects'));
    }

    /**
     * Process the CSV import. (STORY-031)
     * Supports native LinkTracker format and third-party tool format (auto-detected).
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file'   => 'required|file|mimes:csv,txt|max:10240', // 10 MB max
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($request->project_id);

        /** @var \App\Services\BacklinkCsvImportService $importer */
        $importer = app(\App\Services\BacklinkCsvImportService::class);
        $result   = $importer->import($request->file('csv_file'), $project);

        if (!empty($result['errors']) && $result['imported'] === 0) {
            return back()->withErrors(['csv_file' => $result['errors'][0]])->withInput();
        }

        $message = "{$result['imported']} backlink(s) importé(s).";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} ignoré(s) (doublons ou erreurs).";
        }
        if (!empty($result['errors'])) {
            $message .= ' Certaines lignes ont des erreurs : ' . implode('; ', array_slice($result['errors'], 0, 3));
        }

        return redirect()->route('backlinks.index')->with('success', $message);
    }

    /**
     * Export backlinks as CSV. (STORY-035)
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'status'     => 'nullable|in:active,lost,changed',
        ]);

        $query = Backlink::with('project');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $backlinks = $query->orderBy('created_at', 'desc')->get();

        $filename = 'backlinks-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'source_url', 'target_url', 'anchor_text', 'status',
            'tier_level', 'spot_type', 'price', 'currency',
            'published_at', 'expires_at', 'project',
        ];

        $callback = function () use ($backlinks, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            foreach ($backlinks as $backlink) {
                fputcsv($handle, [
                    $backlink->source_url,
                    $backlink->target_url,
                    $backlink->anchor_text,
                    $backlink->status,
                    $backlink->tier_level,
                    $backlink->spot_type,
                    $backlink->price,
                    $backlink->currency,
                    $backlink->published_at?->format('Y-m-d'),
                    $backlink->expires_at?->format('Y-m-d'),
                    $backlink->project?->name,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk delete backlinks.
     * POST /backlinks/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:backlinks,id',
        ]);

        $deleted = Backlink::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', "{$deleted} backlink(s) supprimé(s).");
    }

    /**
     * Bulk check backlinks (dispatch a CheckBacklinkJob for each).
     * POST /backlinks/bulk-check
     */
    public function bulkCheck(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:backlinks,id',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            \App\Jobs\CheckBacklinkJob::dispatch(Backlink::find($id));
            $count++;
        }

        return redirect()->back()->with('success', "{$count} vérification(s) lancée(s) en arrière-plan.");
    }

    /**
     * Bulk edit backlinks (published_at, status, is_indexed).
     * POST /backlinks/bulk-edit
     */
    public function bulkEdit(Request $request)
    {
        $request->validate([
            'ids'          => 'required|array|min:1|max:500',
            'ids.*'        => 'integer|exists:backlinks,id',
            'field'        => 'required|in:published_at,status,is_indexed,is_dofollow',
            'value'        => 'nullable|string|max:20',
        ]);

        $field = $request->field;
        $value = $request->value;

        // Conversion selon le champ
        $update = match ($field) {
            'published_at' => ['published_at' => $value ?: null],
            'status'       => in_array($value, ['active', 'lost', 'changed']) ? ['status' => $value] : null,
            'is_indexed'   => ['is_indexed' => $value === '1' ? true : ($value === '0' ? false : null)],
            'is_dofollow'  => ['is_dofollow' => $value === '1'],
            default        => null,
        };

        if ($update === null) {
            return redirect()->back()->withErrors(['field' => 'Valeur invalide.']);
        }

        $updated = Backlink::whereIn('id', $request->ids)->update($update);

        return redirect()->back()->with('success', "{$updated} backlink(s) mis à jour.");
    }

}
