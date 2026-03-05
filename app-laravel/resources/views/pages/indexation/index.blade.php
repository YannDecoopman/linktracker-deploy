@extends('layouts.app')

@section('title', 'Indexation - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Indexation</span>
@endsection

@section('content')
    <x-page-header title="Indexation" subtitle="Soumettez vos backlinks non-indexés et suivez leur progression" />

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div x-data="{
        activeTab: '{{ request('tab', 'submit') }}',
        selected: [],
        allIds: {{ $candidates->pluck('id')->toJson() }},
        selectAll: false,
        toggleAll() {
            this.selectAll = !this.selectAll;
            this.selected = this.selectAll ? [...this.allIds] : [];
        }
    }" class="space-y-6">

        {{-- Navigation onglets --}}
        <div class="border-b border-neutral-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'submit'"
                    :class="activeTab === 'submit' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Soumettre
                    @if($candidates->count() > 0)
                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                            {{ $candidates->count() }}
                        </span>
                    @endif
                </button>
                <button @click="activeTab = 'campaigns'"
                    :class="activeTab === 'campaigns' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Campagnes
                    @if($campaigns->total() > 0)
                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">
                            {{ $campaigns->total() }}
                        </span>
                    @endif
                </button>
                <button @click="activeTab = 'report'"
                    :class="activeTab === 'report' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Rapport
                </button>
            </nav>
        </div>

        {{-- ===== ONGLET SOUMETTRE ===== --}}
        <div x-show="activeTab === 'submit'" x-cloak class="space-y-6">

            {{-- Provider actif --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900">Provider de réindexation</h3>
                        <p class="text-sm text-neutral-500 mt-0.5">
                            Les URLs sélectionnées seront soumises via ce service.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($service->isConfigured())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm font-medium">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                {{ $service->getProviderName() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm font-medium">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                Non configuré
                            </span>
                        @endif
                        <a href="{{ route('settings.index', ['tab' => 'indexation']) }}"
                            class="text-sm text-brand-600 hover:text-brand-700 transition-colors">
                            Configurer →
                        </a>
                    </div>
                </div>
                @if(! $service->isConfigured())
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                        Aucune clé API configurée. Rendez-vous dans
                        <a href="{{ route('settings.index', ['tab' => 'indexation']) }}" class="underline">Paramètres → Indexation</a>
                        pour ajouter votre clé.
                    </div>
                @endif
            </div>

            {{-- Formulaire de sélection --}}
            @if($candidates->isEmpty())
                <div class="bg-white rounded-xl border border-neutral-200 p-10 text-center">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1">Aucun backlink à indexer</h3>
                    <p class="text-sm text-neutral-500">Tous vos backlinks actifs ont un statut d'indexation déterminé.</p>
                </div>
            @else
                <form method="POST" action="{{ route('indexation.campaigns.store') }}" class="space-y-4">
                    @csrf

                    {{-- Nom de la campagne --}}
                    <div class="bg-white rounded-xl border border-neutral-200 p-5">
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">
                            Nom de la campagne <span class="text-neutral-400 font-normal">(optionnel)</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="Ex: Mars 2026 — Tier2 non-indexés"
                            class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm max-w-md">
                    </div>

                    {{-- Barre d'actions contextuelle --}}
                    <div x-show="selected.length > 0" x-cloak
                        class="sticky top-4 z-10 bg-white border border-brand-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-neutral-900"
                                x-text="selected.length + ' URL(s) sélectionnée(s)'"></span>
                            <button type="button" @click="selected = []; selectAll = false"
                                class="text-xs text-neutral-400 hover:text-neutral-600 transition-colors">
                                Désélectionner tout
                            </button>
                        </div>
                        <button type="submit"
                            :disabled="{{ ! $service->isConfigured() ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Soumettre pour réindexation
                        </button>
                    </div>

                    {{-- Hidden inputs réactifs pour la soumission du formulaire --}}
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="backlink_ids[]" :value="id">
                    </template>

                    {{-- Table des candidats --}}
                    <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
                        <div class="px-3 py-2 border-b border-neutral-100 flex items-center justify-between">
                            <p class="text-sm text-neutral-600">
                                <span class="font-semibold text-neutral-900">{{ $candidates->count() }}</span> backlink(s) candidat(s)
                            </p>
                            <p class="text-xs text-neutral-400">Tier 1 · HTTP 200 · Dofollow · Non indexé / Inconnu</p>
                        </div>
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
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">URL Source</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Ancre</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">URL Cible</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Tier</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Réseau</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Statut</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">HTTP</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">DF</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">Idx</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Vérifié</th>
                                    </tr>
                                </x-slot:header>
                                <x-slot:body>
                                    @foreach($candidates as $backlink)
                                        <x-backlink-row
                                            :backlink="$backlink"
                                            :show-project="true"
                                            :show-price="false"
                                            :show-actions="false"
                                            :show-select="true"
                                        />
                                    @endforeach
                                </x-slot:body>
                            </x-table>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        {{-- ===== ONGLET CAMPAGNES ===== --}}
        <div x-show="activeTab === 'campaigns'" x-cloak class="space-y-4">

            @if($campaigns->isEmpty())
                <div class="bg-white rounded-xl border border-neutral-200 p-10 text-center">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1">Aucune campagne</h3>
                    <p class="text-sm text-neutral-500">Soumettez des backlinks depuis l'onglet "Soumettre" pour créer votre première campagne.</p>
                </div>
            @else
                @foreach($campaigns as $campaign)
                    <div x-data="{
                        status: '{{ $campaign->status }}',
                        submitted: {{ $campaign->submitted_count }},
                        indexed: {{ $campaign->indexed_count }},
                        failed: {{ $campaign->failed_count }},
                        total: {{ $campaign->total_urls }},
                        polling: false,
                        get progressPct() { return this.total > 0 ? Math.round(this.submitted / this.total * 100) : 0; },
                        get indexedPct() { return this.submitted > 0 ? Math.round(this.indexed / this.submitted * 100) : 0; },
                        async poll() {
                            if (!['pending','running'].includes(this.status)) return;
                            const r = await fetch('{{ route('indexation.campaigns.status', $campaign) }}');
                            const d = await r.json();
                            this.status = d.status;
                            this.submitted = d.submitted_count;
                            this.indexed = d.indexed_count;
                            this.failed = d.failed_count;
                            if (['pending','running'].includes(this.status)) {
                                setTimeout(() => this.poll(), 5000);
                            }
                        }
                    }" x-init="poll()"
                    class="bg-white rounded-xl border border-neutral-200 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-semibold text-neutral-900">{{ $campaign->name }}</h3>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $campaign->status === 'completed' ? 'bg-green-100 text-green-700' : ($campaign->status === 'failed' ? 'bg-red-100 text-red-700' : ($campaign->status === 'running' ? 'bg-brand-100 text-brand-700' : ($campaign->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600'))) }}"
                                        x-text="{{ json_encode([
                                            'pending'   => 'En attente',
                                            'running'   => 'En cours',
                                            'completed' => 'Terminé',
                                            'failed'    => 'Échoué',
                                            'partial'   => 'Partiel',
                                        ]) }}[status] ?? status">
                                        {{ $campaign->status_label }}
                                    </span>
                                    <span class="text-xs text-neutral-400">via {{ $campaign->provider }}</span>
                                </div>
                                <p class="text-xs text-neutral-400 mt-0.5">
                                    {{ $campaign->created_at->format('d/m/Y H:i') }}
                                    @if($campaign->submitted_at)
                                        · Soumis {{ $campaign->submitted_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('indexation.campaigns.show', $campaign) }}"
                                class="flex-shrink-0 text-xs text-brand-600 hover:text-brand-700 transition-colors font-medium">
                                Voir détails →
                            </a>
                        </div>

                        {{-- Métriques --}}
                        <div class="mt-4 grid grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-neutral-500">Total</p>
                                <p class="text-lg font-semibold text-neutral-900">{{ $campaign->total_urls }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500">Soumis</p>
                                <p class="text-lg font-semibold text-neutral-900" x-text="submitted">{{ $campaign->submitted_count }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500">Indexés</p>
                                <p class="text-lg font-semibold text-green-600" x-text="indexed">{{ $campaign->indexed_count }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500">Taux</p>
                                <p class="text-lg font-semibold text-neutral-900">
                                    <span x-text="indexedPct + '%'">{{ $campaign->success_rate ? $campaign->success_rate.'%' : '—' }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- Barre de progression soumissions --}}
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-neutral-500 mb-1">
                                <span>Soumissions</span>
                                <span x-text="submitted + '/' + total + ' (' + progressPct + '%)'">{{ $campaign->submitted_count }}/{{ $campaign->total_urls }}</span>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-1.5">
                                <div class="bg-brand-500 h-1.5 rounded-full transition-all duration-500"
                                    :style="'width:' + progressPct + '%'"></div>
                            </div>
                        </div>

                        {{-- Barre de progression indexation --}}
                        <div class="mt-2">
                            <div class="flex justify-between text-xs text-neutral-500 mb-1">
                                <span>Indexation confirmée</span>
                                <span x-text="indexed + '/' + submitted + ' (' + indexedPct + '%)'">{{ $campaign->indexed_count }}/{{ $campaign->submitted_count }}</span>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-1.5">
                                <div class="bg-green-500 h-1.5 rounded-full transition-all duration-500"
                                    :style="'width:' + indexedPct + '%'"></div>
                            </div>
                        </div>

                        {{-- Indicateur de polling --}}
                        <div x-show="['pending','running'].includes(status)" x-cloak
                            class="mt-3 flex items-center gap-1.5 text-xs text-brand-600">
                            <span class="w-1.5 h-1.5 bg-brand-500 rounded-full animate-pulse"></span>
                            Vérification en cours…
                        </div>
                    </div>
                @endforeach

                {{-- Pagination --}}
                @if($campaigns->hasPages())
                    <div class="mt-4">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- ===== ONGLET RAPPORT ===== --}}
        <div x-show="activeTab === 'report'" x-cloak class="space-y-6">

            {{-- KPIs globaux --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-neutral-200 p-4">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Campagnes</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ $stats['total_campaigns'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-neutral-200 p-4">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">URLs soumises</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ number_format($stats['total_submitted']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-neutral-200 p-4">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Confirmées indexées</p>
                    <p class="text-2xl font-semibold text-green-600">{{ number_format($stats['total_indexed']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-neutral-200 p-4">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Taux de succès</p>
                    <p class="text-2xl font-semibold {{ ($stats['success_rate'] ?? 0) >= 50 ? 'text-green-600' : 'text-amber-600' }}">
                        {{ $stats['success_rate'] !== null ? $stats['success_rate'].'%' : '—' }}
                    </p>
                </div>
            </div>

            {{-- Performance par provider --}}
            @if($providerStats->count() > 0)
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100">
                        <h3 class="text-sm font-semibold text-neutral-900">Performance par provider</h3>
                    </div>
                    <table class="min-w-full divide-y divide-neutral-100">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Provider</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Campagnes</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">URLs soumises</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Indexées</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Taux</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-50">
                            @foreach($providerStats as $stat)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-5 py-3">
                                        <span class="text-sm font-medium text-neutral-900">{{ $stat->provider }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-neutral-600">{{ $stat->campaign_count }}</td>
                                    <td class="px-5 py-3 text-sm text-neutral-600">{{ number_format($stat->total_submitted) }}</td>
                                    <td class="px-5 py-3 text-sm text-green-600 font-medium">{{ number_format($stat->total_indexed) }}</td>
                                    <td class="px-5 py-3">
                                        @if($stat->success_rate !== null)
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-neutral-100 rounded-full h-1.5 max-w-[80px]">
                                                    <div class="h-1.5 rounded-full {{ $stat->success_rate >= 50 ? 'bg-green-500' : 'bg-amber-500' }}"
                                                        style="width: {{ min($stat->success_rate, 100) }}%"></div>
                                                </div>
                                                <span class="text-sm font-medium text-neutral-700">{{ $stat->success_rate }}%</span>
                                            </div>
                                        @else
                                            <span class="text-sm text-neutral-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- URLs résistantes --}}
            @if($stubbornUrls->count() > 0)
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100 flex items-center gap-3">
                        <h3 class="text-sm font-semibold text-neutral-900">URLs résistantes</h3>
                        <span class="text-xs text-neutral-500">Soumises 2+ fois sans jamais être indexées</span>
                    </div>
                    <table class="min-w-full divide-y divide-neutral-100">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">URL Source</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Tentatives</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-50">
                            @foreach($stubbornUrls as $url)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-5 py-3">
                                        <a href="{{ $url->source_url }}" target="_blank"
                                            class="text-sm text-neutral-700 hover:text-brand-600 font-mono transition-colors"
                                            title="{{ $url->source_url }}">
                                            {{ Str::limit($url->source_url, 80) }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                                            {{ $url->attempt_count }} tentative(s)
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($stats['total_campaigns'] > 0)
                <div class="bg-white rounded-xl border border-neutral-200 p-8 text-center">
                    <p class="text-sm text-neutral-500">Aucune URL identifiée comme résistante pour l'instant.</p>
                    <p class="text-xs text-neutral-400 mt-1">Une URL apparaît ici si elle a été soumise 2 fois sans jamais être indexée.</p>
                </div>
            @endif

            @if($stats['total_campaigns'] === 0)
                <div class="bg-white rounded-xl border border-neutral-200 p-10 text-center">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1">Aucune donnée</h3>
                    <p class="text-sm text-neutral-500">Lancez votre première campagne depuis l'onglet "Soumettre".</p>
                </div>
            @endif
        </div>

    </div>
@endsection
