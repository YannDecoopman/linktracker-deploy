@extends('layouts.app')

@section('title', $sourceDomain->domain . ' - Domaines Sources - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('domains.index') }}" class="text-neutral-500 hover:text-neutral-700">Domaines Sources</a>
    <span class="mx-2 text-neutral-300">/</span>
    <span class="text-neutral-900 font-medium">{{ $sourceDomain->domain }}</span>
@endsection

@section('content')

@php
    $metric    = $sourceDomain->domainMetric;
    $da        = $metric?->da;
    $dr        = $metric?->dr;
    $spam      = $metric?->spam_score;
    $ref       = $metric?->referring_domains_count;
    $kw        = $metric?->organic_keywords_count;

    $daColor   = is_null($da)   ? 'text-neutral-400' : ($da >= 40  ? 'text-emerald-600' : ($da >= 20  ? 'text-amber-500' : 'text-red-500'));
    $drColor   = is_null($dr)   ? 'text-neutral-400' : 'text-neutral-800';
    $spamColor = is_null($spam) ? 'text-neutral-400' : ($spam < 5  ? 'text-emerald-600' : ($spam < 15 ? 'text-amber-500' : 'text-red-500'));

    $totalBacklinks = $linkedProjects->sum('count');
    $totalActive    = $linkedProjects->sum('active');
    $totalBudget    = $linkedProjects->sum('price_total');
@endphp

{{-- ══════════════════════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════════════════════ --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-3 flex-wrap">
        <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">{{ $sourceDomain->domain }}</h1>

        {{-- Badge portfolio / externe avec infobulle --}}
        @if($isInPortfolio)
            <span title="Ce domaine appartient à votre portfolio — vous avez des projets actifs sur ce site"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-default">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 1.5l2.39 4.845 5.345.777-3.867 3.769.913 5.32L10 13.77l-4.78 2.441.912-5.32L2.265 7.122l5.345-.777L10 1.5z" clip-rule="evenodd"/>
                </svg>
                Mon site
            </span>
        @else
            <span title="Ce domaine est externe — vous y achetez des liens mais il ne fait pas partie de vos sites"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-500 border border-neutral-200 cursor-default">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Site externe
            </span>
        @endif

        {{-- Tendance prix --}}
        @if($priceTrend['label'])
            @php $tc = match($priceTrend['type']) { 'up' => 'bg-red-50 text-red-600 border-red-200', 'down' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'first' => 'bg-blue-50 text-blue-600 border-blue-200', default => 'bg-neutral-100 text-neutral-500 border-neutral-200' }; @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $tc }}">
                {{ $priceTrend['label'] }}
            </span>
        @endif
    </div>

    <div class="flex items-center gap-2">
        <p class="text-xs text-neutral-400 hidden sm:block">
            1er vu {{ $sourceDomain->first_seen_at?->format('d/m/Y') ?? '—' }}
            @if($sourceDomain->last_synced_at) · sync. {{ $sourceDomain->last_synced_at->diffForHumans() }} @endif
        </p>
        <form method="POST" action="{{ route('domains.refresh-metrics', $sourceDomain->domain) }}">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-neutral-300 text-xs text-neutral-600 rounded-lg hover:bg-neutral-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualiser métriques
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MÉTRIQUES SEO — 7 cards alignées avec le design dashboard
══════════════════════════════════════════════════════════════ --}}
<div class="grid gap-4 mb-2" style="grid-template-columns: repeat(7, minmax(0, 1fr))">

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">DA</p>
        <p class="text-2xl font-black {{ $daColor }} tabular-nums">{{ $da ?? '—' }}</p>
        <p class="text-xs text-neutral-400 mt-1">Domain Authority</p>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">DR</p>
        <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $dr ?? '—' }}</p>
        <p class="text-xs text-neutral-400 mt-1">Domain Rating</p>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">Spam Score</p>
        <p class="text-2xl font-black {{ $spamColor }} tabular-nums">{{ is_null($spam) ? '—' : $spam.'%' }}</p>
        <p class="text-xs text-neutral-400 mt-1">Score de spam</p>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">Ref. Domains</p>
        <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $ref ? number_format($ref) : '—' }}</p>
        <p class="text-xs text-neutral-400 mt-1">Domaines référents</p>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">Mots-clés</p>
        <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $kw ? number_format($kw) : '—' }}</p>
        <p class="text-xs text-neutral-400 mt-1">Organic Keywords</p>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">Backlinks achetés</p>
        <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $totalBacklinks }}</p>
        @if($totalActive > 0)
            <p class="text-xs text-emerald-600 mt-1">{{ $totalActive }} actifs</p>
        @else
            <p class="text-xs text-neutral-400 mt-1">depuis ce domaine</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <p class="text-xs text-neutral-400 mb-1">Budget total</p>
        <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $totalBudget > 0 ? number_format($totalBudget, 0).' €' : '—' }}</p>
        <p class="text-xs text-neutral-400 mt-1">Dépensé ici</p>
    </div>

</div>

@if($metric?->last_updated_at)
    <p class="text-xs text-neutral-400 mb-6">Métriques mises à jour {{ $metric->last_updated_at->diffForHumans() }} · Provider : {{ $metric->provider ?? 'custom' }}</p>
@elseif(!$metric)
    <p class="text-xs text-amber-500 mb-6">Métriques non encore calculées — cliquez sur "Actualiser les métriques".</p>
@else
    <div class="mb-6"></div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     TABLEAU FUSIONNÉ : Projets liés + collapse backlinks
══════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-lg border border-neutral-200 mb-6" x-data>

    <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
        <div>
            <h2 class="text-sm font-semibold text-neutral-900">Projets liés à ce domaine</h2>
            <p class="text-xs text-neutral-400 mt-0.5">{{ $linkedProjects->count() }} projet(s) · {{ $totalBacklinks }} backlink(s) au total</p>
        </div>
        @if($totalActive > 0)
            <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full font-medium">
                {{ $totalActive }} actif(s)
            </span>
        @endif
    </div>

    @if($linkedProjects->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-neutral-400">
            Aucun backlink acheté depuis ce domaine pour l'instant.
        </div>
    @else
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
            <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-xs font-semibold text-neutral-500 uppercase">
                    <th class="px-4 py-2.5 text-left w-6"></th>
                    <th class="px-4 py-2.5 text-left">Projet</th>
                    <th class="px-4 py-2.5 text-center">Backlinks</th>
                    <th class="px-4 py-2.5 text-center">Actifs</th>
                    <th class="px-4 py-2.5 text-center">Perdus</th>
                    <th class="px-4 py-2.5 text-right">Budget</th>
                    <th class="px-4 py-2.5 text-center">Tendance prix</th>
                    <th class="px-4 py-2.5 text-center">Dernier lien</th>
                </tr>
            </thead>
            {{-- Un <tbody x-data> par projet = état partagé entre les 2 lignes --}}
            @foreach($linkedProjects as $row)
                @php
                    $tc = match($row['price_trend']['type'] ?? '') {
                        'up'    => 'text-red-500 bg-red-50 border-red-200',
                        'down'  => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                        'first' => 'text-blue-600 bg-blue-50 border-blue-200',
                        default => 'text-neutral-400 bg-neutral-50 border-neutral-200',
                    };
                @endphp
                <tbody x-data="{ open: false }">

                    {{-- Ligne projet --}}
                    <tr @click="open = !open"
                        class="border-b border-neutral-100 hover:bg-neutral-50 cursor-pointer select-none transition-colors"
                        :class="open ? 'bg-blue-50' : ''">

                        <td class="px-4 py-3 text-center">
                            <svg class="w-4 h-4 text-neutral-400 transition-transform duration-200 mx-auto"
                                :class="open ? 'rotate-90' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-brand-500 flex-shrink-0"></span>
                                <a href="{{ route('projects.show', $row['project']->id) }}"
                                    class="font-semibold text-neutral-900 hover:text-brand-600 hover:underline"
                                    @click.stop>
                                    {{ $row['project']->name }}
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-neutral-100 text-xs font-bold text-neutral-700">
                                {{ $row['count'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs font-semibold {{ $row['active'] > 0 ? 'text-emerald-600' : 'text-neutral-400' }}">
                                {{ $row['active'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs font-semibold {{ $row['lost'] > 0 ? 'text-red-500' : 'text-neutral-400' }}">
                                {{ $row['lost'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-semibold text-neutral-700">
                            {{ $row['price_total'] > 0 ? number_format($row['price_total'], 2).' €' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($row['price_trend']['label'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $tc }}">
                                    {{ $row['price_trend']['label'] }}
                                </span>
                            @else
                                <span class="text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-neutral-400">
                            {{ $row['last_date'] ? \Carbon\Carbon::parse($row['last_date'])->format('d/m/Y') : '—' }}
                        </td>
                    </tr>

                    {{-- Ligne collapse — même tbody donc accès direct à open --}}
                    <tr x-show="open" x-cloak class="bg-neutral-50/80 border-b border-neutral-100">
                        <td colspan="8" class="px-0 py-0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-neutral-200 text-neutral-500 font-semibold uppercase">
                                            <th class="pl-14 pr-2 py-2 text-left">URL source</th>
                                            <th class="px-2 py-2 text-left">URL cible</th>
                                            <th class="px-2 py-2 text-left">Ancre</th>
                                            <th class="px-2 py-2 text-center">Statut</th>
                                            <th class="px-2 py-2 text-center">Spot / Tier</th>
                                            <th class="px-2 py-2 text-center">DF/NF</th>
                                            <th class="px-2 py-2 text-right">Prix</th>
                                            <th class="px-2 py-2 text-center">Publié</th>
                                            <th class="px-2 pr-4 py-2 text-center">Vérifié</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-100">
                                        @foreach($row['backlinks'] as $bl)
                                            @php
                                                $statusBg = match($bl->status) {
                                                    'active'  => 'bg-emerald-100 text-emerald-700',
                                                    'lost'    => 'bg-red-100 text-red-600',
                                                    'changed' => 'bg-amber-100 text-amber-700',
                                                    'pending' => 'bg-neutral-100 text-neutral-500',
                                                    default   => 'bg-neutral-100 text-neutral-500',
                                                };
                                                $statusLabel = match($bl->status) {
                                                    'active'  => 'Actif',
                                                    'lost'    => 'Perdu',
                                                    'changed' => 'Modifié',
                                                    'pending' => 'En attente',
                                                    default   => $bl->status,
                                                };
                                                $spotLabel = $bl->spot_type === 'internal' ? 'Interne (PBN)' : 'Externe';
                                                $spotClass = $bl->spot_type === 'internal' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-neutral-100 text-neutral-600 border-neutral-200';
                                                $tierLabel = $bl->tier_level === 'tier2' ? 'T2' : 'T1';
                                                $tierClass = $bl->tier_level === 'tier2' ? 'text-amber-500' : 'text-neutral-400';
                                            @endphp
                                            <tr class="hover:bg-white transition-colors">
                                                <td class="pl-14 pr-2 py-2.5 max-w-[180px]">
                                                    <a href="{{ $bl->source_url }}" target="_blank" rel="noopener"
                                                        class="text-brand-600 hover:underline truncate block" title="{{ $bl->source_url }}">
                                                        {{ parse_url($bl->source_url, PHP_URL_PATH) ?: '/' }}
                                                    </a>
                                                </td>
                                                <td class="px-2 py-2.5 max-w-[160px]">
                                                    <a href="{{ $bl->target_url }}" target="_blank" rel="noopener"
                                                        class="text-neutral-500 hover:text-brand-600 hover:underline truncate block text-[11px]" title="{{ $bl->target_url }}">
                                                        {{ parse_url($bl->target_url, PHP_URL_PATH) ?: '/' }}
                                                    </a>
                                                </td>
                                                <td class="px-2 py-2.5 max-w-[140px]">
                                                    <span class="truncate block text-neutral-700" title="{{ $bl->anchor_text }}">
                                                        {{ $bl->anchor_text ?: '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-2 py-2.5 text-center">
                                                    <span class="inline-flex px-1.5 py-0.5 rounded-full font-medium {{ $statusBg }}">{{ $statusLabel }}</span>
                                                </td>
                                                <td class="px-2 py-2.5 text-center">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border text-[11px] font-medium {{ $spotClass }}">{{ $spotLabel }}</span>
                                                    <span class="ml-1 text-[11px] font-semibold {{ $tierClass }}">{{ $tierLabel }}</span>
                                                </td>
                                                <td class="px-2 py-2.5 text-center">
                                                    @if($bl->is_dofollow === null)
                                                        <span class="text-neutral-400">?</span>
                                                    @elseif($bl->is_dofollow)
                                                        <span class="font-medium text-emerald-600">DF</span>
                                                    @else
                                                        <span class="font-medium text-amber-500">NF</span>
                                                    @endif
                                                </td>
                                                <td class="px-2 py-2.5 text-right font-medium text-neutral-700">
                                                    {{ $bl->price ? number_format($bl->price, 2).' '.($bl->currency ?? '€') : '—' }}
                                                </td>
                                                <td class="px-2 py-2.5 text-center text-neutral-400">
                                                    {{ $bl->published_at?->format('d/m/Y') ?? '—' }}
                                                </td>
                                                <td class="px-2 pr-4 py-2.5 text-center text-neutral-400">
                                                    {{ $bl->last_checked_at?->diffForHumans() ?? 'Jamais' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>

                </tbody>
            @endforeach
        </table>
        </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     TABLEAU OPPORTUNITÉS : Projets non liés
══════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-lg border border-neutral-200 overflow-hidden"
     x-data="{
        search: '',
        sortCol: 'name',
        sortDir: 'asc',
        projects: {{ Js::from($unlinkedProjects->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'url' => $p->url ?? ''])) }},
        get filtered() {
            let list = this.projects.filter(p =>
                this.search === '' ||
                p.name.toLowerCase().includes(this.search.toLowerCase()) ||
                (p.url && p.url.toLowerCase().includes(this.search.toLowerCase()))
            );
            list.sort((a, b) => {
                let va = a[this.sortCol] ?? '', vb = b[this.sortCol] ?? '';
                return this.sortDir === 'asc'
                    ? String(va).localeCompare(String(vb))
                    : String(vb).localeCompare(String(va));
            });
            return list;
        },
        sort(col) {
            if (this.sortCol === col) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            else { this.sortCol = col; this.sortDir = 'asc'; }
        }
     }">

    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-neutral-100">
        <div>
            <h2 class="text-sm font-semibold text-neutral-900">Opportunités de backlinks</h2>
            <p class="text-xs text-neutral-400 mt-0.5">
                Projets de votre portfolio <strong>non encore liés</strong> à ce domaine
                <span class="ml-1 px-1.5 py-0.5 bg-amber-50 text-amber-600 text-xs font-semibold rounded border border-amber-200">
                    <span x-text="filtered.length"></span> disponible(s)
                </span>
            </p>
        </div>
        <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Filtrer les projets…"
                class="pl-8 pr-3 py-1.5 border border-neutral-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 w-48">
        </div>
    </div>

    <template x-if="projects.length === 0">
        <div class="px-4 py-10 text-center">
            <div class="inline-flex items-center justify-center w-10 h-10 bg-emerald-50 rounded-full mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-neutral-700">Tous vos projets sont liés à ce domaine</p>
            <p class="text-xs text-neutral-400 mt-1">Ce domaine couvre l'intégralité de votre portfolio.</p>
        </div>
    </template>

    <template x-if="projects.length > 0">
        <div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-200 text-xs font-semibold text-neutral-500 uppercase">
                        <th class="px-4 py-2.5 text-left">
                            <button @click="sort('name')" class="flex items-center gap-1 hover:text-neutral-800 transition-colors">
                                Projet
                                <svg class="w-3 h-3 transition-transform" :class="sortCol==='name' && sortDir==='desc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                        </th>
                        <th class="px-4 py-2.5 text-left">
                            <button @click="sort('url')" class="flex items-center gap-1 hover:text-neutral-800 transition-colors">
                                URL
                                <svg class="w-3 h-3 transition-transform" :class="sortCol==='url' && sortDir==='desc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                        </th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <template x-for="proj in filtered" :key="proj.id">
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                                    <a :href="'/projects/' + proj.id"
                                        class="font-medium text-neutral-800 hover:text-brand-600 hover:underline"
                                        x-text="proj.name"></a>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-neutral-400 truncate block max-w-xs" x-text="proj.url || '—'"></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a :href="'/backlinks/create?project_id=' + proj.id + '&source_domain={{ $sourceDomain->domain }}'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Commander un lien
                                </a>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filtered.length === 0">
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-neutral-400">
                                Aucun projet ne correspond à votre recherche.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

</div>

@endsection
