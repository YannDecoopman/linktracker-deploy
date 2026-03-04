{{-- Sidebar Navigation Component --}}

{{-- Mobile Overlay --}}
<div
    x-data="{ open: false }"
    @toggle-mobile-menu.window="open = !open"
    x-show="open"
    x-cloak
    @click="open = false"
    class="fixed inset-0 bg-neutral-900/60 backdrop-blur-sm z-40 lg:hidden"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
></div>

{{-- Sidebar --}}
<aside
    x-data="{ open: false }"
    @toggle-mobile-menu.window="open = !open"
    :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 w-64 bg-neutral-950 z-50 lg:z-40 transition-transform duration-300 ease-in-out flex flex-col"
>
    {{-- Logo --}}
    <div class="h-16 flex items-center justify-between px-5 border-b border-white/10">
        <a href="{{ url('/dashboard') }}" class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-brand-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </div>
            <span class="text-white font-bold text-sm tracking-tight">Link Tracker</span>
        </a>
        <button @click="$dispatch('toggle-mobile-menu')" class="lg:hidden text-white/50 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Navigation principale --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

        @php
            $navItems = [
                ['url' => url('/dashboard'),           'name' => 'Dashboard',   'match' => 'dashboard',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                ['url' => url('/projects'),            'name' => 'Portfolio',   'match' => 'projects*',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
                ['url' => url('/backlinks'),           'name' => 'Backlinks',   'match' => 'backlinks*',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>'],
                ['url' => url('/domains'),             'name' => 'Domaines',    'match' => 'domains*',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>'],
                ['url' => url('/platforms'),          'name' => 'Plateformes', 'match' => 'platforms*',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'],
            ];
        @endphp

        @foreach($navItems as $item)
            @php $active = request()->is($item['match']); @endphp
            <a href="{{ $item['url'] }}"
               @click="$dispatch('toggle-mobile-menu')"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $active
                          ? 'bg-white/10 text-white'
                          : 'text-white/50 hover:text-white/80 hover:bg-white/5' }}">
                <svg class="w-4.5 h-4.5 flex-shrink-0 {{ $active ? 'text-brand-400' : '' }}"
                     style="width:18px;height:18px"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $item['name'] }}</span>

                {{-- Badge alertes --}}
                @if($item['match'] === 'backlinks*' || false)
                @endif
            </a>
        @endforeach

        {{-- Alertes avec badge --}}
        @php $alertActive = request()->is('alerts*'); @endphp
        <a href="{{ route('alerts.index') }}"
           @click="$dispatch('toggle-mobile-menu')"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                  {{ $alertActive ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white/80 hover:bg-white/5' }}">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 class="{{ $alertActive ? 'text-brand-400' : '' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span>Alertes</span>
            @if($unreadAlertsCount > 0)
                <span class="ml-auto inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full">
                    {{ $unreadAlertsCount > 99 ? '99+' : $unreadAlertsCount }}
                </span>
            @endif
        </a>

        {{-- Séparateur --}}
        <div class="my-3 border-t border-white/10"></div>

        {{-- Commandes --}}
        @php
            $pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();
            $orderActive = request()->is('orders*');
        @endphp
        <a href="{{ url('/orders') }}"
           @click="$dispatch('toggle-mobile-menu')"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                  {{ $orderActive ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white/80 hover:bg-white/5' }}">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 class="{{ $orderActive ? 'text-brand-400' : '' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span>Commandes</span>
            @if($pendingOrdersCount > 0)
                <span class="ml-auto inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-amber-500 rounded-full">
                    {{ $pendingOrdersCount }}
                </span>
            @endif
        </a>

        {{-- Import CSV --}}
        @php $importActive = request()->routeIs('backlinks.import'); @endphp
        <a href="{{ route('backlinks.import') }}"
           @click="$dispatch('toggle-mobile-menu')"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                  {{ $importActive ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white/80 hover:bg-white/5' }}">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 class="{{ $importActive ? 'text-brand-400' : '' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
            </svg>
            <span>Import CSV</span>
        </a>

        {{-- Indexation (EPIC-015) --}}
        @php $indexationActive = request()->is('indexation*'); @endphp
        <a href="{{ route('indexation.index') }}"
           @click="$dispatch('toggle-mobile-menu')"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                  {{ $indexationActive ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white/80 hover:bg-white/5' }}">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 class="{{ $indexationActive ? 'text-brand-400' : '' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Indexation</span>
        </a>

    </nav>

    {{-- Footer sidebar --}}
    <div class="px-3 py-4 border-t border-white/10">
        @php $settingsActive = request()->is('settings*'); @endphp
        <a href="{{ url('/settings') }}"
           @click="$dispatch('toggle-mobile-menu')"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                  {{ $settingsActive ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white/80 hover:bg-white/5' }}">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Paramètres</span>
        </a>

        @auth
        <div class="mt-3 flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/5">
            <div class="w-7 h-7 rounded-full bg-brand-500 flex items-center justify-center flex-shrink-0 text-xs font-bold text-white">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Utilisateur' }}</p>
                <p class="text-xs text-white/40 truncate">{{ auth()->user()->email ?? '' }}</p>
            </div>
        </div>
        @endauth
    </div>
</aside>
