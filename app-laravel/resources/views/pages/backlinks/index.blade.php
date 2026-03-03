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
    <x-backlink-filters
        :action="route('backlinks.index')"
        :reset-url="route('backlinks.index')"
        :active-count="$activeFiltersCount"
        :projects="$projects"
    />

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
                            <x-sortable-header field="anchor_text" label="Ancre" class="px-3 py-2" />
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">URL Cible</th>
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
                            <x-backlink-row :backlink="$backlink" :show-project="true" />
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
