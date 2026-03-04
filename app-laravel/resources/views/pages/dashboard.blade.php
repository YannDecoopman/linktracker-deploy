@extends('layouts.app')

@section('title', 'Dashboard — Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-semibold">Dashboard</span>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     KPI STRIP — Score de santé + pilotage global
     ═══════════════════════════════════════════════════════════ --}}
@php
    $score = $healthScore ?? 0;
    $scoreColor  = $score >= 75 ? 'text-emerald-600' : ($score >= 50 ? 'text-amber-500' : 'text-red-500');
    $scoreBg     = $score >= 75 ? 'bg-emerald-50' : ($score >= 50 ? 'bg-amber-50' : 'bg-red-50');
    $scoreBorder = $score >= 75 ? 'border-emerald-200' : ($score >= 50 ? 'border-amber-200' : 'border-red-200');
    $scoreLabel  = $score >= 75 ? 'Bonne santé' : ($score >= 50 ? 'Attention' : 'Critique');
    $scoreStroke = $score >= 75 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444');
@endphp
<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">

    {{-- Score de santé --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 flex flex-col items-center justify-center">
        <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Score de santé</p>
        <div class="relative w-24 h-24 mb-2">
            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3"/>
                <circle cx="18" cy="18" r="15.9" fill="none"
                    stroke="{{ $scoreStroke }}"
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
        <p class="text-xs text-neutral-400 mt-2">{{ $totalProjects ?? 0 }} sites · {{ $totalBacklinks ?? 0 }} backlinks</p>
    </div>

    {{-- 6 KPI cards --}}
    <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-3 gap-4">

        {{-- Total / Actifs --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Backlinks actifs</p>
            <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $activeBacklinks ?? 0 }}</p>
            <div class="flex gap-2 mt-1.5 text-xs">
                @if(($lostBacklinks ?? 0) > 0)
                    <span class="text-red-500">{{ $lostBacklinks }} perdus</span>
                @endif
                @if(($changedBacklinks ?? 0) > 0)
                    <span class="text-amber-500">{{ $changedBacklinks }} modifiés</span>
                @endif
                @if(($lostBacklinks ?? 0) === 0 && ($changedBacklinks ?? 0) === 0)
                    <span class="text-emerald-600 font-semibold">Tout OK</span>
                @endif
            </div>
        </div>

        {{-- Liens de qualité --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Liens de qualité</p>
            <p class="text-2xl font-black text-emerald-600 tabular-nums">{{ $qualityLinks ?? 0 }}</p>
            <p class="text-xs text-neutral-400 mt-1">Actif + indexé + dofollow</p>
        </div>

        {{-- Non indexés --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Non indexés</p>
            <p class="text-2xl font-black {{ ($unknownIndexed ?? 0) > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">{{ $unknownIndexed ?? 0 }}</p>
            <div class="flex flex-col gap-0.5 mt-1">
                <p class="text-xs text-neutral-400">à vérifier</p>
                @if(($notIndexed ?? 0) > 0)
                    <p class="text-xs text-red-500 font-semibold">{{ $notIndexed }} noindex confirmés</p>
                @endif
            </div>
        </div>

        {{-- Nofollow --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Nofollow</p>
            <p class="text-2xl font-black {{ ($notDofollow ?? 0) > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">{{ $notDofollow ?? 0 }}</p>
            <p class="text-xs text-neutral-400 mt-1">liens sans jus SEO</p>
        </div>

        {{-- Budget total --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Budget total</p>
            <p class="text-2xl font-black text-neutral-900 tabular-nums">
                @if(($budgetTotal ?? 0) > 0)
                    {{ number_format($budgetTotal, 0, ',', ' ') }} €
                @else
                    <span class="text-neutral-300">—</span>
                @endif
            </p>
            @if(($budgetActive ?? 0) > 0 && ($budgetActive ?? 0) != ($budgetTotal ?? 0))
                <p class="text-xs text-neutral-400 mt-1">{{ number_format($budgetActive, 0, ',', ' ') }} € actifs</p>
            @endif
        </div>

        {{-- Uptime --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Uptime · 30j</p>
            @if(!is_null($uptimeRate ?? null))
                <p class="text-2xl font-black tabular-nums {{ ($uptimeRate >= 90) ? 'text-emerald-500' : (($uptimeRate >= 70) ? 'text-amber-500' : 'text-red-500') }}">
                    {{ $uptimeRate }}<span class="text-sm font-bold text-neutral-400">%</span>
                </p>
                <p class="text-xs text-neutral-400 mt-1">{{ $totalChecks ?? 0 }} vérifs</p>
            @else
                <p class="text-2xl font-black tabular-nums text-neutral-200">—</p>
                <p class="text-xs text-neutral-400 mt-1">pas de données</p>
            @endif
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     GRAPHIQUES — courbes qualité + bougies gains/pertes
     ═══════════════════════════════════════════════════════════ --}}
<div class="mb-6 space-y-4" x-data="backlinkChart()">

    {{-- Header partagé : titre + sélecteur période + toggles --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wide">Évolution des backlinks</h2>
            {{-- Sélecteur période (contrôle les deux graphiques) --}}
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
            {{-- Boutons toggle des séries --}}
            <div class="flex flex-wrap gap-2 mb-3">
                <button @click="toggleSeries(0)"
                        :class="t0 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-blue-200 bg-blue-50 text-blue-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Total
                </button>
                <button @click="toggleSeries(1)"
                        :class="t1 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>Actifs
                </button>
                <button @click="toggleSeries(2)"
                        :class="t2 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-red-200 bg-red-50 text-red-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>Perdus
                </button>
                <button @click="toggleSeries(3)"
                        :class="t3 ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-amber-200 bg-amber-50 text-amber-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>Modifiés
                </button>
            </div>
            {{-- Canvas graphique 1 --}}
            <div x-show="loading" class="flex items-center justify-center h-56">
                <div class="flex gap-1.5">
                    <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-10 bg-neutral-300 rounded-full animate-pulse" style="animation-delay:100ms"></span>
                    <span class="w-1.5 h-8 bg-neutral-200 rounded-full animate-pulse" style="animation-delay:200ms"></span>
                    <span class="w-1.5 h-12 bg-neutral-300 rounded-full animate-pulse" style="animation-delay:300ms"></span>
                    <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse" style="animation-delay:400ms"></span>
                </div>
            </div>
            <div x-show="!loading" x-cloak class="relative h-56">
                <canvas id="chartQuality"></canvas>
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
            <div x-show="!loading" x-cloak class="relative h-32">
                <canvas id="chartCandles"></canvas>
            </div>
            <div x-show="loading" class="h-32"></div>
        </div>

        {{-- Bande de stats --}}
        <div class="grid grid-cols-3 divide-x divide-neutral-100 border-t border-neutral-100">
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400 mb-0.5">Total backlinks</p>
                <p class="text-lg font-black text-neutral-900 tabular-nums">{{ ($activeBacklinks ?? 0) + ($lostBacklinks ?? 0) + ($changedBacklinks ?? 0) }}</p>
            </div>
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400 mb-0.5">Taux de succès</p>
                @php
                    $total = ($activeBacklinks ?? 0) + ($lostBacklinks ?? 0) + ($changedBacklinks ?? 0);
                    $successRate = $total > 0 ? round(($activeBacklinks ?? 0) / $total * 100) : 0;
                @endphp
                <p class="text-lg font-black tabular-nums {{ $successRate >= 80 ? 'text-emerald-600' : ($successRate >= 60 ? 'text-amber-500' : 'text-red-500') }}">{{ $successRate }}%</p>
            </div>
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400 mb-0.5">Sites actifs</p>
                <p class="text-lg font-black text-neutral-900 tabular-nums">{{ $totalProjects ?? 0 }}</p>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     ONGLETS — Alertes / Nouveaux Backlinks / Portfolio
     ═══════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-neutral-200 overflow-hidden"
     x-data="{ tab: 'alerts' }">

    {{-- Barre d'onglets --}}
    <div class="flex border-b border-neutral-100">

        {{-- Onglet Alertes --}}
        <button @click="tab = 'alerts'"
                :class="tab === 'alerts' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-50'"
                class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold transition-all duration-150 relative">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span>Alertes récentes</span>
            @if(count($recentAlerts ?? []) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full">
                    {{ count($recentAlerts) }}
                </span>
            @endif
        </button>

        {{-- Onglet Nouveaux Backlinks --}}
        <button @click="tab = 'backlinks'"
                :class="tab === 'backlinks' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-50'"
                class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold transition-all duration-150">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <span>Nouveaux backlinks</span>
            @if(count($recentBacklinks ?? []) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-brand-500 rounded-full">
                    {{ count($recentBacklinks) }}
                </span>
            @endif
        </button>

        {{-- Onglet Portfolio --}}
        <button @click="tab = 'portfolio'"
                :class="tab === 'portfolio' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-50'"
                class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold transition-all duration-150">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Portfolio</span>
            @if(count($recentProjects ?? []) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-neutral-500 bg-neutral-100 rounded-full">
                    {{ count($recentProjects) }}
                </span>
            @endif
        </button>

        {{-- Lien rapide à droite --}}
        <div class="ml-auto flex items-center px-5">
            <a x-show="tab === 'alerts'" href="{{ route('alerts.index') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-medium">Voir tout →</a>
            <a x-show="tab === 'backlinks'" href="{{ route('backlinks.index') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-medium">Voir tout →</a>
            <a x-show="tab === 'portfolio'" href="{{ url('/projects') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-medium">Voir tout →</a>
        </div>
    </div>

    {{-- ── Contenu : Alertes ── --}}
    <div x-show="tab === 'alerts'" x-cloak>
        @if(count($recentAlerts ?? []) > 0)
            <div class="divide-y divide-neutral-50">
                @foreach($recentAlerts as $alert)
                    @php
                        $colors = [
                            'critical' => 'text-red-600 bg-red-50 border-red-200',
                            'high'     => 'text-orange-600 bg-orange-50 border-orange-200',
                            'medium'   => 'text-amber-600 bg-amber-50 border-amber-200',
                            'low'      => 'text-neutral-500 bg-neutral-50 border-neutral-200',
                        ];
                        $dotColors = [
                            'critical' => 'bg-red-500',
                            'high'     => 'bg-orange-400',
                            'medium'   => 'bg-amber-400',
                            'low'      => 'bg-neutral-300',
                        ];
                        $color = $colors[$alert->severity] ?? $colors['low'];
                        $dot   = $dotColors[$alert->severity] ?? $dotColors['low'];
                    @endphp
                    <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-neutral-50 transition-colors">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dot }} {{ $alert->severity === 'critical' ? 'shadow-[0_0_5px_rgba(239,68,68,0.7)]' : '' }}"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-800 truncate">{{ $alert->title }}</p>
                            <p class="text-xs text-neutral-400 truncate">{{ $alert->backlink->project?->name ?? '—' }} · {{ $alert->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full flex-shrink-0 {{ $color }}">
                            {{ ucfirst($alert->severity) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-600">Tout est en ordre</p>
                <p class="text-xs text-neutral-400 mt-1">Aucune alerte récente</p>
            </div>
        @endif
    </div>

    {{-- ── Contenu : Nouveaux Backlinks ── --}}
    <div x-show="tab === 'backlinks'" x-cloak>
        @if(count($recentBacklinks ?? []) > 0)
            <div class="overflow-x-auto" x-data="{ selected: [] }">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Site</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">URL Source</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Ancre</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">URL Cible</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Tier</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Réseau</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Statut</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">DF</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-neutral-500 uppercase">Idx</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-neutral-500 uppercase whitespace-nowrap">Vérifié</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($recentBacklinks as $backlink)
                            <x-backlink-row :backlink="$backlink" :show-project="true" :show-price="false" :show-actions="false" :show-select="false" />
                        @endforeach
                    </x-slot:body>
                </x-table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <div class="w-10 h-10 rounded-full bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-600">Aucun backlink récent</p>
                <a href="{{ route('backlinks.create') }}" class="mt-3 text-xs text-brand-600 hover:text-brand-700 font-semibold">Ajouter un backlink →</a>
            </div>
        @endif
    </div>

    {{-- ── Contenu : Portfolio ── --}}
    <div x-show="tab === 'portfolio'" x-cloak>
        @if(count($recentProjects ?? []) > 0)
            <div class="divide-y divide-neutral-50">
                @foreach($recentProjects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-neutral-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-100 transition-colors">
                            <span class="text-xs font-bold text-brand-600">{{ strtoupper(substr($project->name, 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-neutral-800 truncate group-hover:text-brand-600 transition-colors">{{ $project->name }}</p>
                            <p class="text-xs text-neutral-400 truncate">{{ $project->url }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-neutral-900 tabular-nums">{{ $project->backlinks_count ?? 0 }}</p>
                            <p class="text-xs text-neutral-400">liens</p>
                        </div>
                        <svg style="width:14px;height:14px" class="text-neutral-300 group-hover:text-brand-400 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-neutral-100">
                <a href="{{ url('/projects/create') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">+ Ajouter un site</a>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <div class="w-10 h-10 rounded-full bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-600">Portfolio vide</p>
                <a href="{{ url('/projects/create') }}" class="mt-3 text-xs text-brand-600 hover:text-brand-700 font-semibold">Ajouter votre premier site →</a>
            </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function backlinkChart(projectId = null) {
    let chartQuality = null;
    let chartCandles = null;
    return {
        days: 30,
        loading: true,
        t0: true, t1: true, t2: true, t3: true,

        init() {
            this.loadCharts(30);
        },

        async loadCharts(days) {
            this.days = days;
            this.loading = true;

            const url = projectId
                ? `/api/dashboard/chart?days=${days}&project_id=${projectId}`
                : `/api/dashboard/chart?days=${days}`;

            try {
                const response = await fetch(url);
                const data = await response.json();
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

        // ── Graphique 1 : courbes cumulatives par statut (snapshots réels) ──
        renderQuality(data) {
            const ctx = document.getElementById('chartQuality');
            if (!ctx) return;
            if (chartQuality) chartQuality.destroy();

            const tooltipLabels = ['Total', 'Actifs', 'Perdus', 'Modifiés'];

            chartQuality = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Total',
                            data: data.total || data.active || [],
                            borderColor: 'rgba(59, 130, 246, 0.9)',
                            backgroundColor: 'rgba(59, 130, 246, 0.06)',
                            borderWidth: 2.5,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.4,
                            fill: true,
                            hidden: !this.t0,
                        },
                        {
                            label: 'Actifs',
                            data: data.active || [],
                            borderColor: 'rgba(16, 185, 129, 0.9)',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.4,
                            fill: false,
                            hidden: !this.t1,
                        },
                        {
                            label: 'Perdus',
                            data: data.lost || [],
                            borderColor: 'rgba(239, 68, 68, 0.9)',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [4, 3],
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.4,
                            fill: false,
                            hidden: !this.t2,
                        },
                        {
                            label: 'Modifiés',
                            data: data.changed || [],
                            borderColor: 'rgba(245, 158, 11, 0.9)',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [4, 3],
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.4,
                            fill: false,
                            hidden: !this.t3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            titleColor: 'rgba(148, 163, 184, 1)',
                            bodyColor: '#fff',
                            borderColor: 'rgba(51, 65, 85, 0.5)',
                            borderWidth: 1,
                            padding: 10,
                            titleFont: { size: 11, weight: '600' },
                            bodyFont: { size: 12, weight: '700' },
                            callbacks: {
                                label: (item) => ` ${tooltipLabels[item.datasetIndex]} : ${item.parsed.y}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 10 },
                        },
                        y: {
                            position: 'left',
                            beginAtZero: false,
                            grid: { color: 'rgba(148, 163, 184, 0.1)' },
                            border: { display: false },
                            ticks: { font: { size: 10 }, color: '#94a3b8', precision: 0, maxTicksLimit: 5 },
                        },
                    },
                },
            });
        },

        // ── Graphique 2 : bougies gains / pertes ─────────────────────────
        renderCandles(data) {
            const ctx = document.getElementById('chartCandles');
            if (!ctx) return;
            if (chartCandles) chartCandles.destroy();

            chartCandles = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Gains',
                            data: data.gained || [],
                            backgroundColor: 'rgba(52, 211, 153, 0.8)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                            borderRadius: 3,
                            borderSkipped: false,
                        },
                        {
                            label: 'Pertes',
                            data: (data.lostDelta || []).map(v => -v),
                            backgroundColor: 'rgba(248, 113, 113, 0.8)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1,
                            borderRadius: 3,
                            borderSkipped: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            titleColor: 'rgba(148, 163, 184, 1)',
                            bodyColor: '#fff',
                            borderColor: 'rgba(51, 65, 85, 0.5)',
                            borderWidth: 1,
                            padding: 10,
                            titleFont: { size: 11, weight: '600' },
                            bodyFont: { size: 12, weight: '700' },
                            callbacks: {
                                label: (item) => {
                                    const v = item.datasetIndex === 0 ? item.parsed.y : -item.parsed.y;
                                    const sign = item.datasetIndex === 0 ? '+' : '-';
                                    return ` ${item.dataset.label} : ${sign}${v}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 10 },
                        },
                        y: {
                            grid: { color: 'rgba(148, 163, 184, 0.08)' },
                            border: { display: false },
                            ticks: {
                                font: { size: 10 },
                                color: '#94a3b8',
                                precision: 0,
                                maxTicksLimit: 4,
                                callback: (v) => v >= 0 ? `+${v}` : v,
                            },
                        },
                    },
                },
            });
        },
    };
}
</script>
@endpush
