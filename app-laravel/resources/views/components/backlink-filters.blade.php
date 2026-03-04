{{--
    Composant partagé : panneau de filtres backlinks.

    Props :
    - $action         : URL du formulaire (obligatoire)
    - $resetUrl       : URL de réinitialisation (obligatoire)
    - $activeCount    : Nombre de filtres actifs (défaut : 0)
    - $projects       : Collection de projets pour le filtre Site (défaut : null — filtre masqué)
--}}
@props([
    'action',
    'resetUrl',
    'activeCount' => 0,
    'projects'    => null,
])

<div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-neutral-900">
            Filtres
            @if($activeCount > 0)
                <x-badge variant="brand" class="ml-2">{{ $activeCount }} actif(s)</x-badge>
            @endif
        </h3>
        @if($activeCount > 0)
            <x-button variant="secondary" size="sm" href="{{ $resetUrl }}">
                Réinitialiser
            </x-button>
        @endif
    </div>

    <form method="GET" action="{{ $action }}" class="space-y-4">
        {{-- Recherche textuelle --}}
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Rechercher dans URL source, ancre ou URL cible..."
            class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
        />

        {{-- Filtres en grille (rangée 1) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Statut --}}
            <select name="status" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Tous les statuts</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actif</option>
                <option value="lost"     {{ request('status') === 'lost'     ? 'selected' : '' }}>Perdu</option>
                <option value="changed"  {{ request('status') === 'changed'  ? 'selected' : '' }}>Modifié</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>En attente</option>
            </select>

            {{-- Site (optionnel) --}}
            @if($projects !== null)
                <select name="project_id" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Tous les sites</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            {{-- Tier --}}
            <select name="tier_level" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Tous les tiers</option>
                <option value="tier1" {{ request('tier_level') === 'tier1' ? 'selected' : '' }}>Tier 1</option>
                <option value="tier2" {{ request('tier_level') === 'tier2' ? 'selected' : '' }}>Tier 2</option>
            </select>

            {{-- Réseau --}}
            <select name="spot_type" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Tous les réseaux</option>
                <option value="external"  {{ request('spot_type') === 'external'  ? 'selected' : '' }}>Externe</option>
                <option value="internal"  {{ request('spot_type') === 'internal'  ? 'selected' : '' }}>Interne (PBN)</option>
            </select>
        </div>

        {{-- Filtres en grille (rangée 2) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- HTTP Status --}}
            <select name="http_status" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Tous les codes HTTP</option>
                <option value="200"  {{ request('http_status') === '200'  ? 'selected' : '' }}>200 — OK</option>
                <option value="301"  {{ request('http_status') === '301'  ? 'selected' : '' }}>301 — Redirect</option>
                <option value="302"  {{ request('http_status') === '302'  ? 'selected' : '' }}>302 — Redirect</option>
                <option value="404"  {{ request('http_status') === '404'  ? 'selected' : '' }}>404 — Not Found</option>
                <option value="403"  {{ request('http_status') === '403'  ? 'selected' : '' }}>403 — Forbidden</option>
                <option value="500"  {{ request('http_status') === '500'  ? 'selected' : '' }}>500 — Server Error</option>
            </select>

            {{-- Dofollow --}}
            <select name="is_dofollow" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Dofollow / Nofollow</option>
                <option value="1" {{ request('is_dofollow') === '1' ? 'selected' : '' }}>Dofollow</option>
                <option value="0" {{ request('is_dofollow') === '0' ? 'selected' : '' }}>Nofollow</option>
            </select>

            {{-- Indexation --}}
            <select name="is_indexed" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Indexation</option>
                <option value="1"    {{ request('is_indexed') === '1'    ? 'selected' : '' }}>Indexé</option>
                <option value="0"    {{ request('is_indexed') === '0'    ? 'selected' : '' }}>Non indexé</option>
                <option value="null" {{ request('is_indexed') === 'null' ? 'selected' : '' }}>Inconnu</option>
            </select>
        </div>

        <div class="flex justify-end">
            <x-button variant="primary" type="submit" size="sm">Appliquer les filtres</x-button>
        </div>
    </form>
</div>
