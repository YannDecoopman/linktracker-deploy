@extends('layouts.app')

@section('title', 'Campagne — ' . $campaign->name . ' - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('indexation.index') }}" class="text-neutral-500 hover:text-neutral-700 transition-colors">Indexation</a>
    <span class="mx-2 text-neutral-300">/</span>
    <span class="text-neutral-900 font-medium">{{ Str::limit($campaign->name, 40) }}</span>
@endsection

@section('content')
    <div x-data="{
        status: '{{ $campaign->status }}',
        submitted: {{ $campaign->submitted_count }},
        indexed: {{ $campaign->indexed_count }},
        failed: {{ $campaign->failed_count }},
        total: {{ $campaign->total_urls }},
        get progressPct() { return this.total > 0 ? Math.round(this.submitted / this.total * 100) : 0; },
        get indexedPct() { return this.submitted > 0 ? Math.round(this.indexed / this.submitted * 100) : 0; },
        async poll() {
            if (!['pending','running'].includes(this.status)) return;
            const r = await fetch('{{ route('indexation.campaigns.status', $campaign) }}');
            const d = await r.json();
            this.status = d.status;
            this.submitted = d.submitted_count;
            this.indexed = d.indexed_count;
            this.failed = d.failed_count;
            if (['pending','running'].includes(this.status)) {
                setTimeout(() => this.poll(), 5000);
            }
        }
    }" x-init="poll()">

        {{-- En-tête --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 flex-wrap mb-1">
                    <h1 class="text-xl font-semibold text-neutral-900">{{ $campaign->name }}</h1>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $campaign->status === 'completed' ? 'bg-green-100 text-green-700' : ($campaign->status === 'failed' ? 'bg-red-100 text-red-700' : ($campaign->status === 'running' ? 'bg-brand-100 text-brand-700' : ($campaign->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600'))) }}"
                        x-text="{{ json_encode([
                            'pending'   => 'En attente',
                            'running'   => 'En cours',
                            'completed' => 'Terminé',
                            'failed'    => 'Échoué',
                            'partial'   => 'Partiel',
                        ]) }}[status] ?? status">
                        {{ $campaign->status_label }}
                    </span>
                </div>
                <p class="text-sm text-neutral-500">
                    Provider : <strong class="text-neutral-700">{{ $campaign->provider }}</strong>
                    · Créée le {{ $campaign->created_at->format('d/m/Y à H:i') }}
                    @if($campaign->submitted_at)
                        · Soumise {{ $campaign->submitted_at->diffForHumans() }}
                    @endif
                    @if($campaign->completed_at)
                        · Terminée {{ $campaign->completed_at->diffForHumans() }}
                    @endif
                </p>
            </div>
            <a href="{{ route('indexation.index', ['tab' => 'campaigns']) }}"
                class="flex-shrink-0 text-sm text-neutral-500 hover:text-neutral-700 transition-colors">
                ← Retour
            </a>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Total</p>
                <p class="text-2xl font-semibold text-neutral-900">{{ $campaign->total_urls }}</p>
            </div>
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Soumis</p>
                <p class="text-2xl font-semibold text-neutral-900" x-text="submitted">{{ $campaign->submitted_count }}</p>
            </div>
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Indexés</p>
                <p class="text-2xl font-semibold text-green-600" x-text="indexed">{{ $campaign->indexed_count }}</p>
            </div>
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Échoués</p>
                <p class="text-2xl font-semibold text-red-500" x-text="failed">{{ $campaign->failed_count }}</p>
            </div>
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Taux</p>
                <p class="text-2xl font-semibold text-neutral-900">
                    <span x-text="indexedPct + '%'">
                        {{ $campaign->success_rate ? $campaign->success_rate.'%' : '—' }}
                    </span>
                </p>
            </div>
        </div>

        {{-- Barres de progression --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-5 mb-6 space-y-3">
            <div>
                <div class="flex justify-between text-xs text-neutral-500 mb-1.5">
                    <span>Soumissions API</span>
                    <span x-text="submitted + '/' + total + ' (' + progressPct + '%)'"></span>
                </div>
                <div class="w-full bg-neutral-100 rounded-full h-2">
                    <div class="bg-brand-500 h-2 rounded-full transition-all duration-500"
                        :style="'width:' + progressPct + '%'"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-xs text-neutral-500 mb-1.5">
                    <span>Indexation confirmée (DataForSEO)</span>
                    <span x-text="indexed + '/' + submitted + ' (' + indexedPct + '%)'"></span>
                </div>
                <div class="w-full bg-neutral-100 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                        :style="'width:' + indexedPct + '%'"></div>
                </div>
            </div>

            {{-- Polling indicator --}}
            <div x-show="['pending','running'].includes(status)" x-cloak
                class="flex items-center gap-1.5 text-xs text-brand-600 pt-1">
                <span class="w-1.5 h-1.5 bg-brand-500 rounded-full animate-pulse"></span>
                Vérification en cours — actualisation automatique toutes les 5 secondes
            </div>
        </div>

        @if($campaign->notes)
            <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 mb-6 text-sm text-neutral-600">
                <strong class="text-neutral-700">Notes :</strong> {{ $campaign->notes }}
            </div>
        @endif

        {{-- Tableau des soumissions --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-neutral-900">
                    Soumissions
                    <span class="ml-1.5 text-neutral-400 font-normal">({{ $submissions->total() }})</span>
                </h3>
                <div class="flex items-center gap-3 text-xs text-neutral-500">
                    <span>Checks : 24h</span>
                    <span>48h</span>
                    <span>7j</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-100">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">URL Source</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Soumis</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-neutral-500 uppercase tracking-wider">24h</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-neutral-500 uppercase tracking-wider">48h</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-neutral-500 uppercase tracking-wider">7j</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Indexé le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50">
                        @forelse($submissions as $submission)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-5 py-3">
                                    <div>
                                        <a href="{{ $submission->source_url }}" target="_blank"
                                            class="text-sm text-neutral-800 hover:text-brand-600 font-mono transition-colors"
                                            title="{{ $submission->source_url }}">
                                            {{ Str::limit($submission->source_url, 60) }}
                                        </a>
                                        @if($submission->backlink && $submission->backlink->project)
                                            <p class="text-xs text-neutral-400 mt-0.5">{{ $submission->backlink->project->name }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $statusClasses = [
                                            'pending'      => 'bg-neutral-100 text-neutral-600',
                                            'submitted'    => 'bg-brand-100 text-brand-700',
                                            'submit_error' => 'bg-red-100 text-red-700',
                                            'indexed'      => 'bg-green-100 text-green-700',
                                            'not_indexed'  => 'bg-amber-100 text-amber-700',
                                            'check_error'  => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$submission->submission_status] ?? 'bg-neutral-100 text-neutral-600' }}">
                                        {{ $submission->status_label }}
                                    </span>
                                    @if($submission->error_message)
                                        <p class="text-xs text-red-500 mt-0.5 max-w-xs truncate" title="{{ $submission->error_message }}">
                                            {{ Str::limit($submission->error_message, 50) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-neutral-500">
                                    {{ $submission->submitted_at?->format('d/m H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($submission->check_24h_at)
                                        @if($submission->last_check_result === true && $submission->indexed_at && $submission->check_24h_at >= $submission->indexed_at->subMinutes(5))
                                            <span class="inline-block w-5 h-5 rounded-full bg-green-100 text-green-600 text-xs flex items-center justify-center font-bold">✓</span>
                                        @elseif($submission->check_24h_at)
                                            <span class="inline-block w-5 h-5 rounded-full bg-neutral-100 text-neutral-400 text-xs flex items-center justify-center">—</span>
                                        @endif
                                    @else
                                        <span class="inline-block w-2 h-2 rounded-full bg-neutral-200 mx-auto"></span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($submission->check_48h_at)
                                        <span class="inline-block w-5 h-5 rounded-full bg-neutral-100 text-neutral-400 text-xs flex items-center justify-center">—</span>
                                    @else
                                        <span class="inline-block w-2 h-2 rounded-full bg-neutral-200 mx-auto"></span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($submission->check_7d_at)
                                        <span class="inline-block w-5 h-5 rounded-full bg-neutral-100 text-neutral-400 text-xs flex items-center justify-center">—</span>
                                    @else
                                        <span class="inline-block w-2 h-2 rounded-full bg-neutral-200 mx-auto"></span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-green-600 font-medium">
                                    {{ $submission->indexed_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-neutral-400">
                                    Aucune soumission trouvée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($submissions->hasPages())
                <div class="px-5 py-4 border-t border-neutral-100">
                    {{ $submissions->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
