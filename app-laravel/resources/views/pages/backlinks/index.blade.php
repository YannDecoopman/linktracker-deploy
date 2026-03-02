@extends('layouts.app')

@section('title', 'Backlinks - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Backlinks</span>
@endsection

@section('content')
    <x-page-header title="Backlinks" subtitle="Tous vos backlinks surveillés">
        <x-slot:actions>
            <x-button variant="primary" href="{{ route('backlinks.create') }}">+ Nouveau backlink</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-neutral-900">
                Filtres
                @if($activeFiltersCount > 0)
                    <x-badge variant="brand" class="ml-2">{{ $activeFiltersCount }} actif(s)</x-badge>
                @endif
            </h3>
            @if(request()->hasAny(['search', 'status', 'project_id', 'tier_level', 'spot_type', 'sort']))
                <x-button variant="secondary" size="sm" href="{{ route('backlinks.index') }}">
                    Réinitialiser tous les filtres
                </x-button>
            @endif
        </div>

        <form method="GET" action="{{ route('backlinks.index') }}" class="space-y-4">
            {{-- Recherche textuelle --}}
            <div>
                <label for="search" class="block text-sm font-medium text-neutral-700 mb-1">Recherche</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Rechercher dans URL source, ancre ou URL cible..."
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
            </div>

            {{-- Filtres en grille --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Status Filter --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-neutral-700 mb-1">Statut</label>
                    <select
                        id="status"
                        name="status"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Perdu</option>
                        <option value="changed" {{ request('status') === 'changed' ? 'selected' : '' }}>Modifié</option>
                    </select>
                </div>

                {{-- Project Filter --}}
                <div>
                    <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">Site</label>
                    <select
                        id="project_id"
                        name="project_id"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tier Level Filter --}}
                <div>
                    <label for="tier_level" class="block text-sm font-medium text-neutral-700 mb-1">Tiers</label>
                    <select
                        id="tier_level"
                        name="tier_level"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="tier1" {{ request('tier_level') === 'tier1' ? 'selected' : '' }}>Tier 1</option>
                        <option value="tier2" {{ request('tier_level') === 'tier2' ? 'selected' : '' }}>Tier 2</option>
                    </select>
                </div>

                {{-- Spot Type Filter --}}
                <div>
                    <label for="spot_type" class="block text-sm font-medium text-neutral-700 mb-1">Type de réseau</label>
                    <select
                        id="spot_type"
                        name="spot_type"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="external" {{ request('spot_type') === 'external' ? 'selected' : '' }}>Externe</option>
                        <option value="internal" {{ request('spot_type') === 'internal' ? 'selected' : '' }}>Interne (PBN)</option>
                    </select>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end">
                <x-button variant="primary" type="submit">
                    Appliquer les filtres
                </x-button>
            </div>
        </form>
    </div>

    {{-- Résultats + sélecteur par page --}}
    <div class="mb-4 flex items-center justify-between gap-4">
        <p class="text-sm text-neutral-600">
            <span class="font-semibold text-neutral-900">{{ $backlinks->total() }}</span> backlink(s) trouvé(s)
            @if($activeFiltersCount > 0)
                <span class="text-neutral-500">({{ $activeFiltersCount }} filtre(s) actif(s))</span>
            @endif
        </p>
        <div class="flex items-center gap-2">
            @if(request('sort'))
                @php $sortLabels = ['created_at' => 'Date', 'source_url' => 'URL', 'status' => 'Statut', 'tier_level' => 'Tier', 'spot_type' => 'Réseau', 'last_checked_at' => 'Vérifié']; @endphp
                <span class="text-xs text-neutral-400">Tri : {{ $sortLabels[request('sort')] ?? '' }} ({{ request('direction') === 'asc' ? '↑' : '↓' }})</span>
            @endif
            <form method="GET" action="{{ route('backlinks.index') }}" class="flex items-center gap-1.5">
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
    </div>

    @if($backlinks->count() > 0)
        <div x-data="bulkActions()" class="space-y-3">

        {{-- Barre d'actions en masse (visible quand sélection > 0) --}}
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
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
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
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
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

            {{-- Bulk edit : indexation --}}
            <form :action="'{{ route('backlinks.bulk-edit') }}'" method="POST" class="flex items-center gap-1.5">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <input type="hidden" name="field" value="is_indexed">
                <select name="value"
                        class="px-2 py-1 text-xs text-neutral-700 border border-neutral-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-brand-400">
                    <option value="1">Indexé</option>
                    <option value="0">Non indexé</option>
                </select>
                <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 border border-neutral-200 rounded-lg transition-colors">
                    Indexation
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
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Site</th>
                            <x-sortable-header field="source_url" label="URL Source" class="px-3 py-2" />
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Ancre</th>
                            <x-sortable-header field="tier_level" label="Tier" class="px-3 py-2" />
                            <x-sortable-header field="spot_type" label="Réseau" class="px-3 py-2" />
                            <x-sortable-header field="status" label="Statut" class="px-3 py-2" />
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">DF</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">Idx</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Prix</th>
                            <x-sortable-header field="last_checked_at" label="Vérifié" class="px-3 py-2" />
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase w-20">Actions</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($backlinks as $backlink)
                            <tr class="hover:bg-neutral-50" :class="selected.includes({{ $backlink->id }}) ? 'bg-brand-50' : ''">
                                <td class="px-3 py-2 w-8">
                                    <input type="checkbox" :value="{{ $backlink->id }}" x-model="selected"
                                           class="w-3.5 h-3.5 rounded border-neutral-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-neutral-900 max-w-[120px] truncate">
                                    {{ $backlink->project?->name ?? 'N/A' }}
                                </td>
                                <td class="px-3 py-2 max-w-[220px]">
                                    <a href="{{ $backlink->source_url }}" target="_blank" class="text-xs text-brand-500 hover:text-brand-600 hover:underline truncate block">
                                        {{ $backlink->source_url }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 max-w-[140px]">
                                    @if($backlink->anchor_text)
                                        <span class="text-xs font-semibold text-neutral-900 truncate block">{{ $backlink->anchor_text }}</span>
                                    @else
                                        <span class="text-xs text-neutral-300 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <x-badge variant="{{ $backlink->tier_level === 'tier1' ? 'neutral' : 'warning' }}">
                                        {{ $backlink->tier_level === 'tier1' ? 'T1' : 'T2' }}
                                    </x-badge>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <x-badge variant="{{ $backlink->spot_type === 'internal' ? 'success' : 'neutral' }}">
                                        {{ $backlink->spot_type === 'internal' ? 'PBN' : 'Ext.' }}
                                    </x-badge>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <x-badge variant="{{ $backlink->status === 'active' ? 'success' : ($backlink->status === 'lost' ? 'danger' : 'warning') }}">
                                        {{ $backlink->status === 'active' ? 'Actif' : ($backlink->status === 'lost' ? 'Perdu' : 'Modifié') }}
                                    </x-badge>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @if($backlink->is_dofollow === true)
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">DF</span>
                                    @elseif($backlink->is_dofollow === false)
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">NF</span>
                                    @else
                                        <span class="text-neutral-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @if($backlink->is_indexed === true)
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">✓</span>
                                    @elseif($backlink->is_indexed === false)
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">✗</span>
                                    @else
                                        <span class="text-neutral-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-neutral-700">
                                    @if($backlink->price && $backlink->currency)
                                        {{ number_format($backlink->price, 0) }} {{ $backlink->currency }}
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-neutral-500">
                                    @if($backlink->last_checked_at)
                                        <span title="{{ $backlink->last_checked_at->format('d/m/Y H:i') }}">
                                            {{ $backlink->last_checked_at->diffForHumans(null, true) }}
                                        </span>
                                    @else
                                        <span class="text-neutral-300">Jamais</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <a href="{{ route('backlinks.show', $backlink) }}" class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-neutral-100 text-neutral-500 hover:text-brand-600 transition-colors" title="Voir">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <span x-data="inlineCheck('{{ route('backlinks.check', $backlink) }}', '{{ csrf_token() }}')" class="relative inline-flex">
                                            <button @click="run()" :disabled="loading"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-brand-50 transition-colors cursor-pointer"
                                                    :class="loading ? 'text-brand-400' : (result === true ? 'text-emerald-500' : (result === false ? 'text-red-500' : 'text-neutral-500 hover:text-brand-600'))"
                                                    title="Vérifier maintenant">
                                                <svg x-show="!loading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                <svg x-show="loading" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </span>
                                        <a href="{{ route('backlinks.edit', $backlink) }}" class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-neutral-100 text-neutral-500 hover:text-brand-600 transition-colors" title="Modifier">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form action="{{ route('backlinks.destroy', $backlink) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer ce backlink ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-red-50 text-neutral-500 hover:text-red-600 transition-colors" title="Supprimer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($backlinks->hasPages())
            <div class="mt-6">
                {{ $backlinks->links() }}
            </div>
        @endif

        </div>{{-- /x-data bulkActions --}}
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🔗</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
            <p class="text-neutral-500 mb-6">Commencez par ajouter des backlinks à surveiller.</p>
            <x-button variant="primary" href="{{ route('backlinks.create') }}">Ajouter un backlink</x-button>
        </div>
    @endif
@endsection

@push('scripts')
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
        result: null, // null = idle, true = présent, false = absent
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
                // Réinitialiser l'icône après 3s
                setTimeout(() => { this.result = null; }, 3000);
            }
        },
    };
}
</script>
@endpush
