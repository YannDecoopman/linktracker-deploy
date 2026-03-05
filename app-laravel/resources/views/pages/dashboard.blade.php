@extends('layouts.app')

@section('title', 'Dashboard — Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-semibold">Dashboard</span>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     KPI STRIP — Camembert + cards pilotage
     ═══════════════════════════════════════════════════════════ --}}
<x-kpi-strip
    :total="$totalBacklinks ?? 0"
    :perfect="$qualityLinks ?? 0"
    :noindex="$notIndexed ?? 0"
    :nofollow="$notDofollow ?? 0"
    :pending="$pendingIndexation ?? 0"
    :lost="$lostBacklinks ?? 0"
    :changed="$changedBacklinks ?? 0"
    :pending-status="$pendingBacklinks ?? 0"
    :budget-total="$budgetTotal ?? null"
    :budget-active="$budgetActive ?? 0"
    :uptime-rate="$uptimeRate ?? null"
    :total-checks="$totalChecks ?? 0"
    :subtitle="($totalProjects ?? 0) . ' sites'"
/>

{{-- ═══════════════════════════════════════════════════════════
     GRAPHIQUES — courbes qualité + bougies gains/pertes
     ═══════════════════════════════════════════════════════════ --}}
<x-backlink-chart
    canvas-id="dashboard"
    :total="$totalBacklinks ?? 0"
    :active="$activeBacklinks ?? 0"
    :projects="$totalProjects ?? 0"
/>

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
