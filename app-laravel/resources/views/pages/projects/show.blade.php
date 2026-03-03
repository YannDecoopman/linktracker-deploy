@extends('layouts.app')

@section('title', $project->name . ' - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}" class="text-neutral-500 hover:text-neutral-700">Portfolio</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">{{ $project->name }}</span>
@endsection

@section('content')
    {{-- Page Header --}}
    <x-page-header :title="$project->name" :subtitle="$project->url">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('projects.report', $project) }}" target="_blank">
                📄 Rapport
            </x-button>
            <x-button variant="secondary" href="{{ route('projects.edit', $project) }}">
                Modifier
            </x-button>
            <x-button variant="primary" href="{{ url('/backlinks/create?project_id=' . $project->id) }}">
                + Ajouter un backlink
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- ═══════════════════════════════════════════════════════════
         SCORE DE SANTÉ + KPI CARDS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">

        {{-- Score de santé (grande card) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-5 flex flex-col items-center justify-center">
            @php
                $score = $stats['health_score'];
                $scoreColor = $score >= 75 ? 'text-emerald-600' : ($score >= 50 ? 'text-amber-500' : 'text-red-500');
                $scoreBg    = $score >= 75 ? 'bg-emerald-50' : ($score >= 50 ? 'bg-amber-50' : 'bg-red-50');
                $scoreBorder = $score >= 75 ? 'border-emerald-200' : ($score >= 50 ? 'border-amber-200' : 'border-red-200');
                $scoreLabel = $score >= 75 ? 'Bonne santé' : ($score >= 50 ? 'Attention' : 'Critique');
            @endphp
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Score de santé</p>
            <div class="relative w-24 h-24 mb-2">
                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.9" fill="none"
                        stroke="{{ $score >= 75 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444') }}"
                        stroke-width="3"
                        stroke-dasharray="{{ $score }}, 100"
                        stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-xl font-black {{ $scoreColor }}">{{ $score }}</span>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold border rounded-full {{ $scoreBg }} {{ $scoreColor }} {{ $scoreBorder }}">
                {{ $scoreLabel }}
            </span>
        </div>

        {{-- KPI cards 3 colonnes --}}
        <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-3 gap-4">

            {{-- Total backlinks --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Total backlinks</p>
                <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $stats['total'] }}</p>
                <div class="flex gap-2 mt-2 text-xs">
                    <span class="text-emerald-600 font-semibold">{{ $stats['active'] }} actifs</span>
                    @if($stats['lost'] > 0)
                        <span class="text-red-500">· {{ $stats['lost'] }} perdus</span>
                    @endif
                </div>
            </div>

            {{-- Qualité (actif + indexé + dofollow) --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Liens de qualité</p>
                <p class="text-2xl font-black text-emerald-600 tabular-nums">{{ $stats['quality'] }}</p>
                <p class="text-xs text-neutral-400 mt-1">Actif + indexé + dofollow</p>
            </div>

            {{-- Non indexés --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Non indexés</p>
                <p class="text-2xl font-black {{ $stats['not_indexed'] > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">
                    {{ $stats['not_indexed'] }}
                </p>
                @if($stats['unknown_indexed'] > 0)
                    <p class="text-xs text-neutral-400 mt-1">+ {{ $stats['unknown_indexed'] }} inconnus</p>
                @endif
            </div>

            {{-- Nofollow --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Nofollow</p>
                <p class="text-2xl font-black {{ $stats['not_dofollow'] > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">
                    {{ $stats['not_dofollow'] }}
                </p>
                <p class="text-xs text-neutral-400 mt-1">liens sans jus SEO</p>
            </div>

            {{-- Budget total --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Budget total</p>
                <p class="text-2xl font-black text-neutral-900 tabular-nums">
                    @if($stats['budget_total'] > 0)
                        {{ number_format($stats['budget_total'], 0, ',', ' ') }} €
                    @else
                        <span class="text-neutral-300">—</span>
                    @endif
                </p>
                @if($stats['budget_active'] > 0 && $stats['budget_active'] != $stats['budget_total'])
                    <p class="text-xs text-neutral-400 mt-1">{{ number_format($stats['budget_active'], 0, ',', ' ') }} € actifs</p>
                @endif
            </div>

            {{-- Perdus --}}
            <div class="bg-white rounded-xl border border-{{ $stats['lost'] > 0 ? 'red-200' : 'neutral-200' }} p-4 {{ $stats['lost'] > 0 ? 'bg-red-50' : 'bg-white' }}">
                <p class="text-xs {{ $stats['lost'] > 0 ? 'text-red-400' : 'text-neutral-400' }} mb-1">Backlinks perdus</p>
                <p class="text-2xl font-black {{ $stats['lost'] > 0 ? 'text-red-600' : 'text-neutral-900' }} tabular-nums">
                    {{ $stats['lost'] }}
                </p>
                @if($stats['changed'] > 0)
                    <p class="text-xs text-amber-500 mt-1">+ {{ $stats['changed'] }} modifiés</p>
                @endif
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         GRAPHIQUES D'ÉVOLUTION
         ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-neutral-200 mb-6 overflow-hidden"
         x-data="backlinkChart({{ $project->id }})">

        {{-- Header : titre + sélecteur période --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wide">Évolution des backlinks</h2>
            <div class="flex gap-1 bg-neutral-100 p-1 rounded-lg">
                @foreach([30 => '30j', 90 => '90j', 180 => '6m', 365 => '1an'] as $d => $label)
                    <button @click="loadCharts({{ $d }})"
                        :class="days === {{ $d }} ? 'bg-white text-neutral-900 shadow-sm font-semibold' : 'text-neutral-500 hover:text-neutral-700'"
                        class="px-3 py-1 text-xs rounded-md transition-all duration-150">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Graphique 1 : courbes cumulatives --}}
        <div class="px-6 pt-4 pb-2">
            {{-- Boutons toggle --}}
            <div class="flex flex-wrap gap-2 mb-3">
                <button @click="toggleSeries(0)" :class="t0 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-blue-200 bg-blue-50 text-blue-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Total
                </button>
                <button @click="toggleSeries(1)" :class="t1 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>Parfaits
                </button>
                <button @click="toggleSeries(2)" :class="t2 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-amber-200 bg-amber-50 text-amber-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>Non indexés
                </button>
                <button @click="toggleSeries(3)" :class="t3 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-violet-200 bg-violet-50 text-violet-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-violet-500 inline-block"></span>Nofollow
                </button>
            </div>
            <div x-show="loading" class="flex items-center justify-center h-44">
                <div class="flex gap-1.5">
                    <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-10 bg-neutral-300 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-8 bg-neutral-200 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-12 bg-neutral-300 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse"></span>
                </div>
            </div>
            <div x-show="!loading" x-cloak class="relative h-44">
                <canvas id="projectChartQuality"></canvas>
            </div>
        </div>

        {{-- Séparateur --}}
        <div class="mx-6 border-t border-neutral-100 my-1"></div>

        {{-- Graphique 2 : bougies gains / pertes --}}
        <div class="px-6 pt-2 pb-4">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wide">Gains &amp; Pertes / jour</span>
                <span class="flex items-center gap-1 text-xs text-neutral-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 inline-block"></span>Gains
                </span>
                <span class="flex items-center gap-1 text-xs text-neutral-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span>Pertes
                </span>
            </div>
            <div x-show="!loading" x-cloak class="relative h-28">
                <canvas id="projectChartCandles"></canvas>
            </div>
            <div x-show="loading" class="h-28"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TABLEAU DES BACKLINKS
         ═══════════════════════════════════════════════════════════ --}}

    {{-- Filtres --}}
    <x-backlink-filters
        :action="route('projects.show', $project)"
        :reset-url="route('projects.show', $project)"
        :active-count="$activeFiltersCount"
    />

    {{-- Résultats + sélecteur par page --}}
    <div class="mb-3 flex items-center justify-between gap-4">
        <p class="text-sm text-neutral-600">
            <span class="font-semibold text-neutral-900">{{ $backlinks->total() }}</span> backlink(s)
            @if($activeFiltersCount > 0)
                <span class="text-neutral-500">({{ $activeFiltersCount }} filtre(s) actif(s))</span>
            @endif
        </p>
        <form method="GET" action="{{ route('projects.show', $project) }}" class="flex items-center gap-1.5">
            @foreach(request()->except('per_page', 'page') as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach
            <label class="text-xs text-neutral-500 whitespace-nowrap">Par page :</label>
            <select name="per_page" onchange="this.form.submit()"
                    class="text-xs border border-neutral-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @foreach([15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ request('per_page', 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($backlinks->count() > 0)
        <div x-data="bulkActions()" class="space-y-3">

        {{-- Barre d'actions en masse --}}
        <div x-show="selected.length > 0" x-cloak
             class="bg-white border border-neutral-200 rounded-xl px-4 py-2.5 flex items-center gap-3 flex-wrap shadow-sm">

            {{-- Compteur --}}
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-700 bg-neutral-100 px-2.5 py-1 rounded-full"
                  x-text="selected.length + ' sélectionné(s)'"></span>

            <div class="w-px h-5 bg-neutral-200 flex-shrink-0"></div>

            {{-- Bulk check --}}
            <button x-show="!checking" @click="runBulkCheck('{{ csrf_token() }}')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-neutral-600 hover:text-brand-600 hover:bg-brand-50 border border-neutral-200 hover:border-brand-200 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Vérifier
            </button>
            {{-- Barre de progression bulk check --}}
            <div x-show="checking" x-cloak class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-brand-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <div class="flex items-center gap-1.5">
                    <div class="w-24 h-1.5 bg-neutral-200 rounded-full overflow-hidden">
                        <div class="h-full bg-brand-500 rounded-full transition-all duration-300"
                             :style="'width:' + (checkTotal > 0 ? Math.round(checkProgress/checkTotal*100) : 0) + '%'"></div>
                    </div>
                    <span class="text-xs text-neutral-500 tabular-nums" x-text="checkProgress + '/' + checkTotal"></span>
                </div>
            </div>

            {{-- Bulk edit : date publication --}}
            <form :action="'{{ route('backlinks.bulk-edit') }}'" method="POST" class="flex items-center gap-1.5">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <input type="hidden" name="field" value="published_at">
                <input type="date" name="value"
                       class="px-2 py-1 text-xs text-neutral-700 border border-neutral-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-400">
                <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 border border-neutral-200 rounded-lg transition-colors whitespace-nowrap">
                    Date pub.
                </button>
            </form>

            {{-- Bulk edit : statut --}}
            <form :action="'{{ route('backlinks.bulk-edit') }}'" method="POST" class="flex items-center gap-1.5">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <input type="hidden" name="field" value="status">
                <select name="value"
                        class="px-2 py-1 text-xs text-neutral-700 border border-neutral-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-400">
                    <option value="active">Actif</option>
                    <option value="lost">Perdu</option>
                    <option value="changed">Modifié</option>
                </select>
                <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 border border-neutral-200 rounded-lg transition-colors">
                    Statut
                </button>
            </form>

            <div class="w-px h-5 bg-neutral-200 flex-shrink-0"></div>

            {{-- Bulk delete --}}
            <form :action="'{{ route('backlinks.bulk-delete') }}'" method="POST" @submit.prevent="confirmBulkDelete($event)">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 hover:border-red-300 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Supprimer
                </button>
            </form>

            <button @click="selected = []"
                    class="ml-auto text-xs text-neutral-400 hover:text-neutral-600 transition-colors">
                ✕ Désélectionner
            </button>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="px-3 py-2 w-8">
                                <input type="checkbox" @change="toggleAll($event)"
                                       :checked="selected.length === allIds.length && allIds.length > 0"
                                       class="w-3.5 h-3.5 rounded border-neutral-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                            </th>
                            <x-sortable-header field="source_url" label="URL Source" class="px-3 py-2" :route="route('projects.show', $project)" />
                            <x-sortable-header field="anchor_text" label="Ancre" class="px-3 py-2" :route="route('projects.show', $project)" />
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">URL Cible</th>
                            <x-sortable-header field="tier_level" label="Tier" class="px-3 py-2" :route="route('projects.show', $project)" />
                            <x-sortable-header field="spot_type" label="Réseau" class="px-3 py-2" :route="route('projects.show', $project)" />
                            <x-sortable-header field="status" label="Statut" class="px-3 py-2" :route="route('projects.show', $project)" />
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">DF</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">Idx</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Prix</th>
                            <x-sortable-header field="last_checked_at" label="Vérifié" class="px-3 py-2" :route="route('projects.show', $project)" />
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase w-20">Actions</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($backlinks as $backlink)
                            <x-backlink-row :backlink="$backlink" />
                        @endforeach
                    </x-slot:body>
                </x-table>
            </div>
        </div>

        @if($backlinks->hasPages())
            <div class="mt-4">{{ $backlinks->links() }}</div>
        @endif

        </div>{{-- /x-data bulkActions --}}
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🔗</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
            <p class="text-neutral-500 mb-6">Ajoutez des backlinks à suivre pour ce site.</p>
            <x-button variant="primary" href="{{ url('/backlinks/create?project_id=' . $project->id) }}">
                + Ajouter un backlink
            </x-button>
        </div>
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function bulkActions() {
    return {
        selected: [],
        allIds: @json($backlinks->pluck('id')),
        checking: false,
        checkProgress: 0,
        checkTotal: 0,

        toggleAll(e) {
            this.selected = e.target.checked ? [...this.allIds] : [];
        },

        confirmBulkDelete(e) {
            if (!confirm(`Supprimer définitivement ${this.selected.length} backlink(s) ? Cette action est irréversible.`)) {
                return;
            }
            e.target.submit();
        },

        async runBulkCheck(token) {
            if (this.checking || this.selected.length === 0) return;
            this.checking = true;
            this.checkProgress = 0;
            this.checkTotal = this.selected.length;
            const ids = [...this.selected];
            for (const id of ids) {
                try {
                    await fetch(`/backlinks/${id}/check`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'fetch',
                            'Accept': 'application/json',
                        },
                    });
                } catch (_) {}
                this.checkProgress++;
            }
            this.checking = false;
            window.location.reload();
        },
    };
}

function inlineCheck(url, token) {
    return {
        loading: false,
        result: null,
        async run() {
            if (this.loading) return;
            this.loading = true;
            this.result = null;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'fetch',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                this.result = data.success ? data.is_present : false;
            } catch {
                this.result = false;
            } finally {
                this.loading = false;
                setTimeout(() => { this.result = null; }, 3000);
            }
        },
    };
}

function backlinkChart(projectId) {
    let chartQuality = null;
    let chartCandles = null;
    return {
        days: 30,
        loading: true,
        t0: true, t1: true, t2: true, t3: true,

        init() { this.loadCharts(30); },

        async loadCharts(d) {
            this.days = d;
            this.loading = true;
            try {
                const res = await fetch(`/api/dashboard/chart?days=${d}&project_id=${projectId}`);
                const data = await res.json();
                this.loading = false;
                await this.$nextTick();
                this.renderQuality(data);
                this.renderCandles(data);
            } catch (e) {
                console.error('Erreur chargement graphique:', e);
                this.loading = false;
            }
        },

        toggleSeries(index) {
            const key = 't' + index;
            this[key] = !this[key];
            if (chartQuality) {
                chartQuality.data.datasets[index].hidden = !this[key];
                chartQuality.update();
            }
        },

        renderQuality(data) {
            const ctx = document.getElementById('projectChartQuality');
            if (!ctx) return;
            if (chartQuality) chartQuality.destroy();
            const tooltipLabels = ['Total', 'Parfaits', 'Non indexés', 'Nofollow'];
            chartQuality = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Total', data: data.active || [], borderColor: 'rgba(59,130,246,0.9)', backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: true, hidden: !this.t0 },
                        { label: 'Parfaits', data: data.perfect || [], borderColor: 'rgba(16,185,129,0.9)', backgroundColor: 'rgba(16,185,129,0.05)', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.t1 },
                        { label: 'Non indexés', data: data.not_indexed || [], borderColor: 'rgba(245,158,11,0.9)', backgroundColor: 'transparent', borderWidth: 2, borderDash: [4, 3], pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.t2 },
                        { label: 'Nofollow', data: data.nofollow || [], borderColor: 'rgba(139,92,246,0.9)', backgroundColor: 'transparent', borderWidth: 2, borderDash: [4, 3], pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.t3 },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)', titleColor: 'rgba(148,163,184,1)', bodyColor: '#fff',
                            borderColor: 'rgba(51,65,85,0.5)', borderWidth: 1, padding: 10,
                            callbacks: { label: (item) => ` ${tooltipLabels[item.datasetIndex]} : ${item.parsed.y}` },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 10 } },
                        y: { position: 'left', beginAtZero: false, grid: { color: 'rgba(148,163,184,0.1)' }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', precision: 0, maxTicksLimit: 5 } },
                    },
                },
            });
        },

        renderCandles(data) {
            const ctx = document.getElementById('projectChartCandles');
            if (!ctx) return;
            if (chartCandles) chartCandles.destroy();
            chartCandles = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Gains', data: data.gained || [], backgroundColor: 'rgba(52,211,153,0.8)', borderColor: 'rgba(16,185,129,1)', borderWidth: 1, borderRadius: 3, borderSkipped: false },
                        { label: 'Pertes', data: (data.lost || []).map(v => -v), backgroundColor: 'rgba(248,113,113,0.8)', borderColor: 'rgba(239,68,68,1)', borderWidth: 1, borderRadius: 3, borderSkipped: false },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)', titleColor: 'rgba(148,163,184,1)', bodyColor: '#fff',
                            borderColor: 'rgba(51,65,85,0.5)', borderWidth: 1, padding: 10,
                            callbacks: { label: (item) => { const v = item.datasetIndex === 0 ? item.parsed.y : -item.parsed.y; return ` ${item.dataset.label} : ${item.datasetIndex === 0 ? '+' : '-'}${v}`; } },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 10 } },
                        y: { grid: { color: 'rgba(148,163,184,0.08)' }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', precision: 0, maxTicksLimit: 4, callback: (v) => v >= 0 ? `+${v}` : v } },
                    },
                },
            });
        },
    };
}
</script>
@endpush
