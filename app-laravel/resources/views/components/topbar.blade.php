{{--
    Topbar Component

    Contient:
    - Mobile hamburger menu button
    - Breadcrumb navigation (slot 'breadcrumb')
    - Quick stats (optionnel)
    - User menu (dropdown)

    TODO: Implémenter dropdown user menu avec AlpineJS
    TODO: Récupérer stats réelles depuis database
--}}

@props([
    'title' => 'Page',
])

<div class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6 sticky top-0 z-30">
    {{-- Left: Mobile Menu Button + Breadcrumb --}}
    <div class="flex items-center space-x-4">
        {{-- Mobile Hamburger Menu Button --}}
        <button
            @click="$dispatch('toggle-mobile-menu')"
            class="lg:hidden p-2 -ml-2 rounded-lg text-neutral-600 hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
            aria-label="Ouvrir le menu"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center space-x-2 text-sm">
            @hasSection('breadcrumb')
                @yield('breadcrumb')
            @else
                {{-- Default breadcrumb si aucun fourni --}}
                <span class="text-neutral-900 font-medium">{{ $title ?? 'Page' }}</span>
            @endif
        </nav>
    </div>

    {{-- Right: Stats + User Menu --}}
    <div class="flex items-center space-x-6">
        {{-- Quick Stats (Optional) --}}
        @php
            use App\Models\Backlink;
            use App\Models\Project;

            $activeBacklinksCount = Backlink::where('status', 'active')->count();
            $projectsCount = Project::count();
        @endphp

        <div class="hidden md:flex items-center space-x-4 text-sm text-neutral-500">
            <span>{{ $activeBacklinksCount }} actifs</span>
            <span class="w-1 h-1 bg-neutral-300 rounded-full"></span>
            <span>{{ $projectsCount }} sites</span>
        </div>

        {{-- User Menu --}}
        <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
            <button
                type="button"
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center space-x-2 hover:opacity-80 transition-opacity"
            >
                {{-- User Avatar --}}
                <div class="w-8 h-8 bg-brand-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    @auth
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    @else
                        U
                    @endauth
                </div>

                {{-- User Name (hidden on mobile) --}}
                <span class="text-sm font-medium text-neutral-700 hidden md:block">
                    @auth
                        {{ auth()->user()->name ?? 'Utilisateur' }}
                    @else
                        Utilisateur
                    @endauth
                </span>

                {{-- Dropdown Arrow --}}
                <svg
                    class="w-4 h-4 text-neutral-400 hidden md:block transition-transform"
                    :class="{ 'rotate-180': userMenuOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Dropdown Menu --}}
            <div
                x-show="userMenuOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-neutral-200 py-1 z-50"
                style="display: none;"
            >
                {{-- User Info --}}
                <div class="px-4 py-3 border-b border-neutral-200">
                    <p class="text-sm font-medium text-neutral-900">
                        @auth
                            {{ auth()->user()->name ?? 'Utilisateur' }}
                        @else
                            Utilisateur
                        @endauth
                    </p>
                    <p class="text-xs text-neutral-500">
                        @auth
                            {{ auth()->user()->email ?? 'email@example.com' }}
                        @else
                            email@example.com
                        @endauth
                    </p>
                </div>

                {{-- Menu Items --}}
                <div class="py-1">
                    <a
                        href="{{ route('dashboard') }}"
                        class="flex items-center px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                    >
                        <svg class="w-4 h-4 mr-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    <a
                        href="{{ route('profile.show') }}"
                        class="flex items-center px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                    >
                        <svg class="w-4 h-4 mr-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Mon profil
                    </a>

                    <a
                        href="{{ route('pages.under-construction') }}"
                        class="flex items-center px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                    >
                        <svg class="w-4 h-4 mr-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Paramètres
                    </a>
                </div>

                {{-- Logout --}}
                <div class="border-t border-neutral-200 py-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex items-center w-full px-4 py-2 text-sm text-danger-600 hover:bg-danger-50"
                        >
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
