{{--
    Composant partagé : graphiques d'évolution des backlinks (Chart.js).
    Utilisé sur /dashboard et /projects/{id}.

    Props :
    - $projectId  : int|null  — null = vue globale
    - $canvasId   : string    — préfixe unique pour les canvas (évite conflits si plusieurs sur la page)
    - $total      : int       — affiché dans la bande stats bas
    - $active     : int
    - $projects   : int|null  — nb de sites (dashboard seulement)
--}}
@props([
    'projectId' => null,
    'canvasId'  => 'chart',
    'total'     => 0,
    'active'    => 0,
    'projects'  => null,
])

@php
    $successRate = $total > 0 ? round($active / $total * 100) : 0;
@endphp

<div class="bg-white rounded-xl border border-neutral-200 mb-6 overflow-hidden"
     x-data="backlinkChart('{{ $canvasId }}', {{ $projectId ?? 'null' }})">

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
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-red-200 bg-red-50 text-red-700 transition-opacity">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>Non indexés
            </button>
            <button @click="toggleSeries(3)" :class="t3 ? 'opacity-100' : 'opacity-40'"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-amber-200 bg-amber-50 text-amber-700 transition-opacity">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>Nofollow
            </button>
        </div>
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
            <canvas :id="canvasId + '-quality'"></canvas>
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
            <canvas :id="canvasId + '-candles'"></canvas>
        </div>
        <div x-show="loading" class="h-32"></div>
    </div>

    {{-- Bande stats --}}
    <div class="grid grid-cols-3 divide-x divide-neutral-100 border-t border-neutral-100">
        <div class="px-6 py-3">
            <p class="text-xs text-neutral-400 mb-0.5">Total enregistrés</p>
            <p class="text-lg font-black text-neutral-900 tabular-nums">{{ $total }}</p>
        </div>
        <div class="px-6 py-3">
            <p class="text-xs text-neutral-400 mb-0.5">Taux actifs</p>
            <p class="text-lg font-black tabular-nums {{ $successRate >= 80 ? 'text-emerald-600' : ($successRate >= 60 ? 'text-amber-500' : 'text-red-500') }}">{{ $successRate }}%</p>
        </div>
        <div class="px-6 py-3">
            @if(!is_null($projects))
                <p class="text-xs text-neutral-400 mb-0.5">Sites</p>
                <p class="text-lg font-black text-neutral-900 tabular-nums">{{ $projects }}</p>
            @else
                <p class="text-xs text-neutral-400 mb-0.5">Actifs</p>
                <p class="text-lg font-black text-neutral-900 tabular-nums">{{ $active }}</p>
            @endif
        </div>
    </div>
</div>

@once
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function backlinkChart(canvasId, projectId) {
    let chartQuality = null;
    let chartCandles = null;
    return {
        canvasId,
        days: 30,
        loading: true,
        t0: true, t1: true, t2: true, t3: true,

        init() { this.loadCharts(30); },

        async loadCharts(days) {
            this.days = days;
            this.loading = true;
            const url = projectId
                ? `/api/dashboard/chart?days=${days}&project_id=${projectId}`
                : `/api/dashboard/chart?days=${days}`;
            try {
                const res = await fetch(url);
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
            const ctx = document.getElementById(this.canvasId + '-quality');
            if (!ctx) return;
            if (chartQuality) chartQuality.destroy();
            const tooltipLabels = ['Total', 'Parfaits', 'Non indexés', 'Nofollow'];
            chartQuality = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Total',       data: data.total       || [], borderColor: 'rgba(59,130,246,0.9)',  backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: true,  hidden: !this.t0 },
                        { label: 'Parfaits',    data: data.perfect     || [], borderColor: 'rgba(16,185,129,0.9)',  backgroundColor: 'rgba(16,185,129,0.05)', borderWidth: 2,   pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.t1 },
                        { label: 'Non indexés', data: data.not_indexed || [], borderColor: 'rgba(239,68,68,0.9)',   backgroundColor: 'transparent',           borderWidth: 2,   pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.t2, borderDash: [4,3] },
                        { label: 'Nofollow',    data: data.nofollow    || [], borderColor: 'rgba(245,158,11,0.9)',  backgroundColor: 'transparent',           borderWidth: 2,   pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.t3, borderDash: [4,3] },
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
                            titleFont: { size: 11, weight: '600' }, bodyFont: { size: 12, weight: '700' },
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
            const ctx = document.getElementById(this.canvasId + '-candles');
            if (!ctx) return;
            if (chartCandles) chartCandles.destroy();
            chartCandles = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Gains',  data: data.gained   || [],                    backgroundColor: 'rgba(52,211,153,0.8)',  borderColor: 'rgba(16,185,129,1)',  borderWidth: 1, borderRadius: 3, borderSkipped: false },
                        { label: 'Pertes', data: (data.lostDelta || []).map(v => -v),    backgroundColor: 'rgba(248,113,113,0.8)', borderColor: 'rgba(239,68,68,1)',   borderWidth: 1, borderRadius: 3, borderSkipped: false },
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
@endonce
