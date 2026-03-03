@extends('layouts.app')

@section('title', 'Domaines Sources - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Domaines Sources</span>
@endsection

@section('content')

    <x-page-header title="Domaines Sources" subtitle="Vue consolidée de tous les domaines depuis lesquels vous avez acheté des backlinks">
        <x-slot name="actions">
            <button @click="$dispatch('open-add-domain')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajouter un domaine
            </button>
        </x-slot>
    </x-page-header>

    {{-- Filtres --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6">
        <form method="GET" action="{{ route('domains.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-neutral-600 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="exemple.com"
                    class="w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-1">DA min</label>
                <input type="number" name="da_min" value="{{ $daMin }}" min="0" max="100" placeholder="0"
                    class="w-24 px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-1">DA max</label>
                <input type="number" name="da_max" value="{{ $daMax }}" min="0" max="100" placeholder="100"
                    class="w-24 px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-1">Spam max</label>
                <input type="number" name="spam_max" value="{{ $spamMax }}" min="0" max="100" placeholder="100"
                    class="w-24 px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <button type="submit"
                class="px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-700 transition-colors">
                Filtrer
            </button>
            @if($search || $daMin || $daMax || $spamMax)
                <a href="{{ route('domains.index') }}"
                    class="px-4 py-2 border border-neutral-300 text-neutral-600 text-sm rounded-lg hover:bg-neutral-50 transition-colors">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Tableau --}}
    <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
            <span class="text-sm text-neutral-500">{{ $domains->total() }} domaine(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-200">
                        @php
                            $sortLink = fn($col) => route('domains.index', array_merge(request()->query(), [
                                'sort' => $col,
                                'direction' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
                            ]));
                            $sortIcon = fn($col) => $sort === $col ? ($dir === 'asc' ? '↑' : '↓') : '';
                        @endphp
                        <th class="px-4 py-2.5 text-left font-medium text-neutral-500 uppercase text-xs">
                            <a href="{{ $sortLink('domain') }}" class="hover:text-neutral-900">Domaine {{ $sortIcon('domain') }}</a>
                        </th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">
                            <a href="{{ $sortLink('da') }}" class="hover:text-neutral-900">DA {{ $sortIcon('da') }}</a>
                        </th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">
                            <a href="{{ $sortLink('dr') }}" class="hover:text-neutral-900">DR {{ $sortIcon('dr') }}</a>
                        </th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">
                            <a href="{{ $sortLink('spam_score') }}" class="hover:text-neutral-900">Spam {{ $sortIcon('spam_score') }}</a>
                        </th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">Ref. Domains</th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">Backlinks</th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">Projets</th>
                        <th class="px-4 py-2.5 text-center font-medium text-neutral-500 uppercase text-xs">1er vu</th>
                        <th class="px-4 py-2.5 text-right font-medium text-neutral-500 uppercase text-xs">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($domains as $domain)
                        @php
                            $da         = $domain->da;
                            $daColor    = is_null($da) ? 'text-neutral-400' : ($da >= 40 ? 'text-green-600' : ($da >= 20 ? 'text-amber-600' : 'text-red-500'));
                            $spam       = $domain->spam_score;
                            $spamColor  = is_null($spam) ? 'text-neutral-400' : ($spam < 5 ? 'text-green-600' : ($spam < 15 ? 'text-amber-600' : 'text-red-500'));
                            $blCount    = $backlinkCountsMap[$domain->domain] ?? 0;
                            $projCount  = $projectCountsMap[$domain->domain] ?? 0;
                        @endphp
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('domains.show', $domain->domain) }}"
                                    class="font-medium text-brand-600 hover:text-brand-800 hover:underline">
                                    {{ $domain->domain }}
                                </a>
                                @if(is_null($domain->da) && is_null($domain->dr))
                                    <span class="ml-1.5 text-xs text-neutral-400 italic">métriques en attente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-semibold {{ $daColor }}">
                                {{ $da ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-neutral-600">
                                {{ $domain->dr ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold {{ $spamColor }}">
                                {{ is_null($spam) ? '—' : $spam . '%' }}
                            </td>
                            <td class="px-4 py-3 text-center text-neutral-600">
                                {{ $domain->referring_domains_count ? number_format($domain->referring_domains_count) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 text-xs font-semibold bg-neutral-100 text-neutral-700 rounded-full">
                                    {{ $blCount }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-neutral-600">{{ $projCount }}</td>
                            <td class="px-4 py-3 text-center text-xs text-neutral-400">
                                {{ $domain->first_seen_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('domains.show', $domain->domain) }}"
                                    class="text-xs text-brand-600 hover:text-brand-800 font-medium">Voir →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-neutral-400 text-sm">
                                Aucun domaine source trouvé.
                                @if(!$search)
                                    Les domaines sont synchronisés automatiquement depuis vos backlinks.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($domains->hasPages())
            <div class="px-4 py-3 border-t border-neutral-100">
                {{ $domains->links() }}
            </div>
        @endif
    </div>

    {{-- Modal ajout domaine --}}
    <div x-data="{ open: false }" @open-add-domain.window="open = true">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/60 backdrop-blur-sm"
            @keydown.escape.window="open = false">
            <div @click.outside="open = false" class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Ajouter un domaine</h2>
                <form method="POST" action="{{ route('domains.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Nom de domaine</label>
                        <input type="text" name="domain" placeholder="exemple.com" autofocus
                            class="w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-neutral-400">Format : exemple.com (sans https:// ni www.)</p>
                        @error('domain')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="open = false"
                            class="px-4 py-2 text-sm text-neutral-600 border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                            Annuler
                        </button>
                        <x-button type="submit" variant="primary">Ajouter</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
