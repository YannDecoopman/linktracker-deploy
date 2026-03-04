{{--
    Composant partagé : ligne de tableau backlink.

    Props :
    - $backlink      : instance Backlink (obligatoire)
    - $showProject   : afficher la colonne "Site" (défaut : false)
    - $showPrice     : afficher la colonne "Prix" (défaut : true)
    - $showActions   : afficher les boutons voir/vérifier/éditer/supprimer (défaut : true)
    - $showSelect    : afficher la checkbox de sélection (défaut : true)
--}}
@props([
    'backlink',
    'showProject' => false,
    'showPrice'   => true,
    'showActions' => true,
    'showSelect'  => true,
])

<tr class="hover:bg-neutral-50 transition-colors"
    @if($showSelect) :class="selected.includes({{ $backlink->id }}) ? 'bg-brand-50' : ''" @endif>

    {{-- Checkbox --}}
    @if($showSelect)
        <td class="px-3 py-2 w-8">
            <input type="checkbox" :value="{{ $backlink->id }}" x-model="selected"
                   class="w-3.5 h-3.5 rounded border-neutral-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
        </td>
    @endif

    {{-- Site (projet) --}}
    @if($showProject)
        <td class="px-3 py-2 whitespace-nowrap">
            <a href="{{ route('projects.show', $backlink->project_id) }}"
               class="text-xs font-semibold text-neutral-700 hover:text-brand-600">
                {{ $backlink->project?->name ?? '—' }}
            </a>
        </td>
    @endif

    {{-- URL Source --}}
    <td class="px-3 py-2 max-w-[200px]">
        <a href="{{ $backlink->source_url }}" target="_blank"
           class="text-xs text-brand-500 hover:text-brand-600 hover:underline truncate block"
           title="{{ $backlink->source_url }}">
            {{ $backlink->source_url }}
        </a>
    </td>

    {{-- Ancre --}}
    <td class="px-3 py-2 max-w-[140px]">
        @if($backlink->anchor_text)
            <span class="text-xs text-neutral-600 italic truncate block" title="{{ $backlink->anchor_text }}">{{ $backlink->anchor_text }}</span>
        @else
            <span class="text-neutral-300 text-xs">—</span>
        @endif
    </td>

    {{-- URL Cible --}}
    <td class="px-3 py-2 max-w-[160px]">
        <a href="{{ $backlink->target_url }}" target="_blank"
           class="text-xs text-neutral-500 hover:text-brand-600 hover:underline truncate block"
           title="{{ $backlink->target_url }}">
            {{ $backlink->target_url }}
        </a>
    </td>

    {{-- Tier --}}
    <td class="px-3 py-2 whitespace-nowrap">
        <x-badge variant="{{ $backlink->tier_level === 'tier1' ? 'neutral' : 'warning' }}">
            {{ $backlink->tier_level === 'tier1' ? 'T1' : 'T2' }}
        </x-badge>
    </td>

    {{-- Réseau --}}
    <td class="px-3 py-2 whitespace-nowrap">
        <x-badge variant="{{ $backlink->spot_type === 'internal' ? 'success' : 'neutral' }}">
            {{ $backlink->spot_type === 'internal' ? 'PBN' : 'Ext.' }}
        </x-badge>
    </td>

    {{-- Statut --}}
    <td class="px-3 py-2 whitespace-nowrap">
        @php
            $statusVariant = match($backlink->status) {
                'active'  => 'success',
                'lost'    => 'danger',
                'changed' => 'warning',
                default   => 'neutral',
            };
            $statusLabel = match($backlink->status) {
                'active'  => 'Actif',
                'lost'    => 'Perdu',
                'changed' => 'Modifié',
                'pending' => 'En attente',
                default   => $backlink->status,
            };
            $statusTooltip = match($backlink->status) {
                'active'  => 'Le lien est présent sur la page source et pointe vers l\'URL cible.',
                'lost'    => 'Le lien n\'a pas été trouvé lors du dernier check (page inaccessible ou lien supprimé).',
                'changed' => 'L\'ancre, l\'URL cible ou l\'attribut rel a changé depuis le dernier check.',
                'pending' => 'Jamais vérifié — en attente du premier check.',
                default   => '',
            };
        @endphp
        <span title="{{ $statusTooltip }}" class="cursor-help">
            <x-badge variant="{{ $statusVariant }}">{{ $statusLabel }}</x-badge>
        </span>
    </td>

    {{-- HTTP Status --}}
    <td class="px-3 py-2 text-center whitespace-nowrap">
        @if($backlink->http_status)
            @php
                $httpColor = match(true) {
                    $backlink->http_status >= 200 && $backlink->http_status < 300 => 'text-emerald-600',
                    $backlink->http_status >= 300 && $backlink->http_status < 400 => 'text-amber-500',
                    default => 'text-red-500',
                };
            @endphp
            <span class="text-xs font-mono font-semibold {{ $httpColor }}"
                  title="Code HTTP retourné lors du dernier check">
                {{ $backlink->http_status }}
            </span>
        @else
            <span class="text-neutral-300 text-xs">—</span>
        @endif
    </td>

    {{-- Dofollow --}}
    <td class="px-3 py-2 text-center whitespace-nowrap">
        @if($backlink->is_dofollow === true)
            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">DF</span>
        @elseif($backlink->is_dofollow === false)
            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">NF</span>
        @else
            <span class="text-neutral-300 text-xs">—</span>
        @endif
    </td>

    {{-- Indexé --}}
    <td class="px-3 py-2 text-center whitespace-nowrap">
        @if($backlink->is_indexed === true)
            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">✓</span>
        @elseif($backlink->is_indexed === false)
            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">✗</span>
        @else
            <span class="text-neutral-300 text-xs">—</span>
        @endif
    </td>

    {{-- Prix --}}
    @if($showPrice)
        <td class="px-3 py-2 whitespace-nowrap text-xs text-neutral-700">
            @if($backlink->price && $backlink->currency)
                {{ number_format($backlink->price, 0) }} {{ $backlink->currency }}
            @else
                <span class="text-neutral-300">—</span>
            @endif
        </td>
    @endif

    {{-- Dernière vérification --}}
    <td class="px-3 py-2 whitespace-nowrap text-xs text-neutral-500">
        @if($backlink->last_checked_at)
            <span title="{{ $backlink->last_checked_at->format('d/m/Y H:i') }}">
                {{ $backlink->last_checked_at->diffForHumans(null, true) }}
            </span>
        @else
            <span class="text-neutral-300">Jamais</span>
        @endif
    </td>

    {{-- Actions --}}
    @if($showActions)
        <td class="px-3 py-2 whitespace-nowrap text-center">
            <div class="flex items-center justify-center gap-0.5">
                <a href="{{ route('backlinks.show', $backlink) }}"
                   class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-neutral-100 text-neutral-500 hover:text-brand-600 transition-colors"
                   title="Voir">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </a>
                <span x-data="inlineCheck('{{ route('backlinks.check', $backlink) }}', '{{ csrf_token() }}')" class="relative inline-flex">
                    <button @click="run()" :disabled="loading"
                            class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-brand-50 transition-colors cursor-pointer"
                            :class="loading ? 'text-brand-400' : (result === true ? 'text-emerald-500' : (result === false ? 'text-red-500' : 'text-neutral-500 hover:text-brand-600'))"
                            title="Vérifier maintenant">
                        <svg x-show="!loading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="loading" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </span>
                <a href="{{ route('backlinks.edit', $backlink) }}"
                   class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-neutral-100 text-neutral-500 hover:text-brand-600 transition-colors"
                   title="Modifier">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
                <form action="{{ route('backlinks.destroy', $backlink) }}" method="POST" class="inline-block"
                      onsubmit="return confirm('Supprimer ce backlink ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-red-50 text-neutral-500 hover:text-red-600 transition-colors"
                            title="Supprimer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
        </td>
    @endif
</tr>
