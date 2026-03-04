{{--
    Composant partagé : bande KPI (camembert + cards).
    Utilisé sur /dashboard et /projects/{id}.

    Props requises :
    - $total          : int  — total backlinks
    - $perfect        : int  — actif + indexé + dofollow
    - $noindex        : int  — is_indexed = false
    - $nofollow       : int  — actif + http200 + nofollow
    - $pending        : int  — actif/changed + is_indexed null
    - $lost           : int
    - $changed        : int
    - $pendingStatus  : int  — status = pending

    Props optionnelles :
    - $uptimeRate     : float|null
    - $totalChecks    : int
    - $budgetTotal    : float|null
    - $budgetActive   : float|null
    - $subtitle       : string|null  — ex: "8 sites" sous le camembert
--}}
@props([
    'total'         => 0,
    'perfect'       => 0,
    'noindex'       => 0,
    'nofollow'      => 0,
    'pending'       => 0,
    'lost'          => 0,
    'changed'       => 0,
    'pendingStatus' => 0,
    'uptimeRate'    => null,
    'totalChecks'   => 0,
    'budgetTotal'   => null,
    'budgetActive'  => null,
    'subtitle'      => null,
])

@php
    $pctPerfect  = $total > 0 ? round($perfect  / $total * 100, 1) : 0;
    $pctNoindex  = $total > 0 ? round($noindex  / $total * 100, 1) : 0;
    $pctNofollow = $total > 0 ? round($nofollow / $total * 100, 1) : 0;
    $pctPending  = $total > 0 ? round($pending  / $total * 100, 1) : 0;

    $offPerfect  = 25;
    $offNoindex  = $offPerfect  + $pctPerfect;
    $offNofollow = $offNoindex  + $pctNoindex;
    $offPending  = $offNofollow + $pctNofollow;

    // 6e card : budget si disponible, sinon uptime
    $showBudget = !is_null($budgetTotal);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">

    {{-- ── Camembert répartition ── --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 flex flex-col items-center justify-center gap-3">
        <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wide self-start">Répartition</p>
        <div class="relative w-28 h-28">
            <svg class="w-28 h-28 -rotate-90" viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3.5"/>
                @if($pctPerfect > 0)
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#10b981" stroke-width="3.5"
                    stroke-dasharray="{{ $pctPerfect }} {{ 100 - $pctPerfect }}"
                    stroke-dashoffset="{{ -($offPerfect - 25) }}"/>
                @endif
                @if($pctNoindex > 0)
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#ef4444" stroke-width="3.5"
                    stroke-dasharray="{{ $pctNoindex }} {{ 100 - $pctNoindex }}"
                    stroke-dashoffset="{{ -($offNoindex - 25) }}"/>
                @endif
                @if($pctNofollow > 0)
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f59e0b" stroke-width="3.5"
                    stroke-dasharray="{{ $pctNofollow }} {{ 100 - $pctNofollow }}"
                    stroke-dashoffset="{{ -($offNofollow - 25) }}"/>
                @endif
                @if($pctPending > 0)
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#6366f1" stroke-width="3.5"
                    stroke-dasharray="{{ $pctPending }} {{ 100 - $pctPending }}"
                    stroke-dashoffset="{{ -($offPending - 25) }}"/>
                @endif
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-xl font-black text-neutral-900">{{ $total }}</span>
                <span class="text-xs text-neutral-400">liens</span>
            </div>
        </div>
        <div class="w-full space-y-1 text-xs">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>Parfaits</span>
                <span class="font-semibold text-neutral-700">{{ $perfect }} <span class="text-neutral-400 font-normal">({{ $pctPerfect }}%)</span></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>Non indexés</span>
                <span class="font-semibold text-neutral-700">{{ $noindex }} <span class="text-neutral-400 font-normal">({{ $pctNoindex }}%)</span></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>Nofollow</span>
                <span class="font-semibold text-neutral-700">{{ $nofollow }} <span class="text-neutral-400 font-normal">({{ $pctNofollow }}%)</span></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>Idx. inconnu</span>
                <span class="font-semibold text-neutral-700">{{ $pending }} <span class="text-neutral-400 font-normal">({{ $pctPending }}%)</span></span>
            </div>
        </div>
        @if($subtitle)
            <p class="text-xs text-neutral-400 self-start">{{ $subtitle }}</p>
        @endif
    </div>

    {{-- ── 6 KPI cards ── --}}
    <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-3 gap-4">

        {{-- Total enregistrés --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Total enregistrés</p>
            <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $total }}</p>
            <div class="flex flex-wrap gap-2 mt-1.5 text-xs">
                @if($lost > 0)
                    <span class="text-red-500">{{ $lost }} perdus</span>
                @endif
                @if($changed > 0)
                    <span class="text-amber-500">{{ $changed }} modifiés</span>
                @endif
                @if($pendingStatus > 0)
                    <span class="text-neutral-400">{{ $pendingStatus }} en attente</span>
                @endif
                @if($lost === 0 && $changed === 0)
                    <span class="text-emerald-600 font-semibold">Tout OK</span>
                @endif
            </div>
        </div>

        {{-- Liens parfaits --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Liens parfaits</p>
            <p class="text-2xl font-black text-emerald-600 tabular-nums">{{ $perfect }}</p>
            <p class="text-xs text-neutral-400 mt-1">Actif + indexé + dofollow</p>
        </div>

        {{-- Non indexés confirmés --}}
        <div class="bg-white rounded-xl border border-{{ $noindex > 0 ? 'red' : 'neutral' }}-200 p-4 {{ $noindex > 0 ? 'bg-red-50' : 'bg-white' }}">
            <p class="text-xs text-neutral-400 mb-1">Non indexés confirmés</p>
            <p class="text-2xl font-black {{ $noindex > 0 ? 'text-red-600' : 'text-neutral-900' }} tabular-nums">{{ $noindex }}</p>
            <p class="text-xs text-neutral-400 mt-1">
                @if($noindex > 0)
                    <a href="{{ route('indexation.index') }}" class="text-red-500 hover:text-red-600 font-medium">→ Réindexer</a>
                @else
                    is_indexed = false
                @endif
            </p>
        </div>

        {{-- Indexation inconnue --}}
        <div class="bg-white rounded-xl border border-{{ $pending > 0 ? 'indigo' : 'neutral' }}-200 p-4 {{ $pending > 0 ? 'bg-indigo-50' : 'bg-white' }}">
            <p class="text-xs text-neutral-400 mb-1">Indexation inconnue</p>
            <p class="text-2xl font-black {{ $pending > 0 ? 'text-indigo-600' : 'text-neutral-900' }} tabular-nums">{{ $pending }}</p>
            <p class="text-xs text-neutral-400 mt-1">Actifs non vérifiés</p>
        </div>

        {{-- Nofollow --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-400 mb-1">Nofollow</p>
            <p class="text-2xl font-black {{ $nofollow > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">{{ $nofollow }}</p>
            <p class="text-xs text-neutral-400 mt-1">liens sans jus SEO</p>
        </div>

        {{-- Budget (si dispo) ou Uptime --}}
        @if($showBudget)
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Budget total</p>
                <p class="text-2xl font-black text-neutral-900 tabular-nums">
                    @if($budgetTotal > 0)
                        {{ number_format($budgetTotal, 0, ',', ' ') }} €
                    @else
                        <span class="text-neutral-300">—</span>
                    @endif
                </p>
                @if($budgetActive > 0 && $budgetActive != $budgetTotal)
                    <p class="text-xs text-neutral-400 mt-1">{{ number_format($budgetActive, 0, ',', ' ') }} € actifs</p>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Uptime · 30j</p>
                @if(!is_null($uptimeRate))
                    <p class="text-2xl font-black tabular-nums {{ $uptimeRate >= 90 ? 'text-emerald-500' : ($uptimeRate >= 70 ? 'text-amber-500' : 'text-red-500') }}">
                        {{ $uptimeRate }}<span class="text-sm font-bold text-neutral-400">%</span>
                    </p>
                    <p class="text-xs text-neutral-400 mt-1">{{ $totalChecks }} vérifs</p>
                @else
                    <p class="text-2xl font-black tabular-nums text-neutral-200">—</p>
                    <p class="text-xs text-neutral-400 mt-1">pas de données</p>
                @endif
            </div>
        @endif

    </div>
</div>
