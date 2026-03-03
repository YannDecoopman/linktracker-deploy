@extends('layouts.app')

@section('title', $sourceDomain->domain . ' - Domaines Sources - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('domains.index') }}" class="text-neutral-500 hover:text-neutral-700">Domaines Sources</a>
    <span class="mx-2 text-neutral-300">/</span>
    <span class="text-neutral-900 font-medium">{{ $sourceDomain->domain }}</span>
@endsection

@section('content')

    @php
        $metric = $sourceDomain->domainMetric;
        $da     = $metric?->da;
        $dr     = $metric?->dr;
        $spam   = $metric?->spam_score;
        $ref    = $metric?->referring_domains_count;
        $kw     = $metric?->organic_keywords_count;

        $daColor   = is_null($da)   ? 'text-neutral-400' : ($da >= 40   ? 'text-green-600'  : ($da >= 20   ? 'text-amber-600'  : 'text-red-500'));
        $spamColor = is_null($spam) ? 'text-neutral-400' : ($spam < 5   ? 'text-green-600'  : ($spam < 15  ? 'text-amber-600'  : 'text-red-500'));
    @endphp

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">{{ $sourceDomain->domain }}</h1>
            <p class="text-sm text-neutral-500 mt-0.5">
                Vu pour la première fois le {{ $sourceDomain->first_seen_at?->format('d/m/Y') ?? '—' }}
                @if($sourceDomain->last_synced_at)
                    · Synchronisé {{ $sourceDomain->last_synced_at->diffForHumans() }}
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('domains.refresh-metrics', $sourceDomain->domain) }}">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 border border-neutral-300 text-sm text-neutral-700 rounded-lg hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualiser les métriques
            </button>
        </form>
    </div>

    {{-- Section 1 : Hero strip métriques SEO --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg border border-neutral-200 p-4 text-center">
            <p class="text-xs text-neutral-500 uppercase font-medium mb-1">DA</p>
            <p class="text-3xl font-bold {{ $daColor }}">{{ $da ?? '—' }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Domain Authority</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-4 text-center">
            <p class="text-xs text-neutral-500 uppercase font-medium mb-1">DR</p>
            <p class="text-3xl font-bold text-neutral-700">{{ $dr ?? '—' }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Domain Rating</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-4 text-center">
            <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Spam</p>
            <p class="text-3xl font-bold {{ $spamColor }}">{{ is_null($spam) ? '—' : $spam . '%' }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Spam Score</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-4 text-center">
            <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Ref. Domains</p>
            <p class="text-3xl font-bold text-neutral-700">{{ $ref ? number_format($ref) : '—' }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Domaines référents</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-4 text-center">
            <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Mots-clés</p>
            <p class="text-3xl font-bold text-neutral-700">{{ $kw ? number_format($kw) : '—' }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Organic Keywords</p>
        </div>
    </div>

    @if($metric && $metric->last_updated_at)
        <p class="text-xs text-neutral-400 -mt-6 mb-8">
            Métriques mises à jour {{ $metric->last_updated_at->diffForHumans() }}
            · Provider : {{ $metric->provider ?? 'custom' }}
        </p>
    @elseif(!$metric)
        <p class="text-xs text-amber-500 -mt-6 mb-8">Métriques non encore calculées — cliquez sur "Actualiser les métriques".</p>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Section 2 : Matrice couverture projets --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-neutral-100">
                    <h2 class="text-sm font-semibold text-neutral-900">Couverture Projets</h2>
                    <p class="text-xs text-neutral-400 mt-0.5">Projets liés à ce domaine</p>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse($projectCoverage as $row)
                        <div class="flex items-center justify-between px-4 py-2.5 {{ $row['linked'] ? '' : 'opacity-60' }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $row['linked'] ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-400' }}">
                                    {{ $row['linked'] ? '✓' : '✗' }}
                                </span>
                                <span class="text-sm font-medium text-neutral-800 truncate">{{ $row['project']->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                @if($row['count'] > 0)
                                    <span class="text-xs text-neutral-500">{{ $row['count'] }} lien(s)</span>
                                @endif
                                @if($row['price_trend']['label'])
                                    @php
                                        $trendClass = match($row['price_trend']['type']) {
                                            'up'    => 'text-red-500',
                                            'down'  => 'text-green-600',
                                            'first' => 'text-blue-500',
                                            default => 'text-neutral-400',
                                        };
                                    @endphp
                                    <span class="text-xs {{ $trendClass }}">{{ $row['price_trend']['label'] }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-neutral-400">Aucun projet créé.</div>
                    @endforelse
                </div>
            </div>

            {{-- Indicateur prix global --}}
            @if($priceTrend['label'])
                <div class="mt-4 bg-white rounded-lg border border-neutral-200 px-4 py-3">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Tendance prix globale</p>
                    @php
                        $trendClass = match($priceTrend['type']) {
                            'up'    => 'text-red-500 bg-red-50',
                            'down'  => 'text-green-600 bg-green-50',
                            'first' => 'text-blue-600 bg-blue-50',
                            default => 'text-neutral-500 bg-neutral-50',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold {{ $trendClass }}">
                        {{ $priceTrend['label'] }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Section 3 : Backlinks achetés sur ce domaine --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-neutral-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-neutral-900">Backlinks depuis ce domaine</h2>
                    <span class="text-xs text-neutral-400">{{ $backlinks->total() }} au total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-neutral-50 border-b border-neutral-200">
                                <th class="px-4 py-2.5 text-left font-medium text-neutral-500 uppercase text-xs">Projet</th>
                                <th class="px-4 py-2.5 text-left font-medium text-neutral-500 uppercase text-xs">Ancre</th>
                                <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">Statut</th>
                                <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">Type</th>
                                <th class="px-4 py-2.5 text-right font-medium text-neutral-500 uppercase text-xs">Prix</th>
                                <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">Publié</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($backlinks as $bl)
                                <tr class="hover:bg-neutral-50 transition-colors">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('projects.show', $bl->project_id) }}"
                                            class="text-xs font-medium text-brand-600 hover:underline">
                                            {{ $bl->project->name ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2.5 max-w-32">
                                        <span class="text-xs text-neutral-700 truncate block" title="{{ $bl->anchor_text }}">
                                            {{ $bl->anchor_text ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @php
                                            $statusBg = match($bl->status) {
                                                'active'  => 'bg-green-100 text-green-700',
                                                'lost'    => 'bg-red-100 text-red-700',
                                                'changed' => 'bg-amber-100 text-amber-700',
                                                default   => 'bg-neutral-100 text-neutral-500',
                                            };
                                            $statusLabel = match($bl->status) {
                                                'active'  => 'Actif',
                                                'lost'    => 'Perdu',
                                                'changed' => 'Modifié',
                                                default   => $bl->status,
                                            };
                                        @endphp
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusBg }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-xs text-neutral-500">
                                        {{ $bl->is_dofollow === null ? '?' : ($bl->is_dofollow ? 'DF' : 'NF') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-xs font-medium text-neutral-700">
                                        {{ $bl->price ? number_format($bl->price, 2) . ' ' . ($bl->currency ?? '€') : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-xs text-neutral-400">
                                        {{ $bl->published_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-400">
                                        Aucun backlink trouvé pour ce domaine.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($backlinks->hasPages())
                    <div class="px-4 py-3 border-t border-neutral-100">
                        {{ $backlinks->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
