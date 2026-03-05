@extends('layouts.app')

@section('title', 'Paramètres - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Paramètres</span>
@endsection

@section('content')
    <x-page-header title="Paramètres" subtitle="Configurez tous les aspects de votre instance LinkTracker" />

    {{-- Navigation par onglets --}}
    <div x-data="{ activeTab: '{{ request('tab', 'monitoring') }}' }" class="space-y-6">

        <div class="border-b border-neutral-200">
            <nav class="-mb-px flex space-x-8" aria-label="Onglets">
                <button @click="activeTab = 'monitoring'"
                    :class="activeTab === 'monitoring' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Monitoring
                </button>
                <button @click="activeTab = 'seo'"
                    :class="activeTab === 'seo' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    APIs SEO
                </button>
                <button @click="activeTab = 'dataforseo'"
                    :class="activeTab === 'dataforseo' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    DataforSEO
                </button>
                <button @click="activeTab = 'indexation'"
                    :class="activeTab === 'indexation' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Indexation
                </button>
                <a href="{{ route('settings.webhook') }}"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 transition-colors">
                    Webhook
                </a>
                <button @click="activeTab = 'account'"
                    :class="activeTab === 'account' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Compte
                </button>
            </nav>
        </div>

        {{-- Onglet Monitoring --}}
        <div x-show="activeTab === 'monitoring'" x-cloak class="space-y-6">

            {{-- KPI de statut queue --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg border border-neutral-200 p-4">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Total backlinks</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ $queueStats['total'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-neutral-200 p-4">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">Vérifiés auj.</p>
                    <p class="text-2xl font-semibold text-green-600">{{ $queueStats['checked_today'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-{{ $queueStats['overdue'] > 0 ? 'amber' : 'neutral' }}-200 p-4 {{ $queueStats['overdue'] > 0 ? 'bg-amber-50' : 'bg-white' }}">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">En retard (&gt;24h)</p>
                    <p class="text-2xl font-semibold {{ $queueStats['overdue'] > 0 ? 'text-amber-600' : 'text-neutral-900' }}">{{ $queueStats['overdue'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-{{ $queueStats['pending'] > 0 ? 'blue' : 'neutral' }}-200 p-4 {{ $queueStats['pending'] > 0 ? 'bg-blue-50' : 'bg-white' }}">
                    <p class="text-xs text-neutral-500 uppercase font-medium mb-1">En queue</p>
                    <p class="text-2xl font-semibold {{ $queueStats['pending'] > 0 ? 'text-blue-600' : 'text-neutral-900' }}">{{ $queueStats['pending'] }}</p>
                    @if($queueStats['failed'] > 0)
                        <p class="text-xs text-red-500 mt-1">{{ $queueStats['failed'] }} échoué(s)</p>
                    @endif
                </div>
            </div>

            {{-- Lancer une vérification manuelle --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl">
                <h2 class="text-base font-semibold text-neutral-900 mb-1">Lancer une vérification</h2>
                <p class="text-sm text-neutral-500 mb-5">Dispatche les jobs de vérification dans la queue. Un worker doit être actif pour les traiter.</p>

                <form method="POST" action="{{ route('settings.monitoring.run-check') }}">
                    @csrf
                    <div class="flex items-end gap-3 flex-wrap">
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1">Périmètre</label>
                            <select name="frequency" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
                                <option value="daily">Non vérifiés depuis 24h</option>
                                <option value="weekly">Non vérifiés depuis 7j</option>
                                <option value="all">Tous les backlinks</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1">Statut</label>
                            <select name="status" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
                                <option value="all">Tous</option>
                                <option value="active">Actifs uniquement</option>
                                <option value="lost">Perdus uniquement</option>
                                <option value="changed">Modifiés uniquement</option>
                            </select>
                        </div>
                        <x-button type="submit" variant="primary">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Lancer la vérification
                        </x-button>
                    </div>
                </form>

                {{-- Instructions worker --}}
                <div class="mt-5 p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                    <p class="text-xs font-medium text-neutral-700 mb-1.5">Démarrer le worker (terminal)</p>
                    <code class="text-xs text-neutral-600 font-mono">php artisan queue:work --timeout=120</code>
                    <p class="text-xs text-neutral-400 mt-1">En prod, utilisez Supervisor pour un worker permanent.</p>
                </div>
            </div>

            {{-- Paramètres de monitoring --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Paramètres de monitoring</h2>

                <form method="POST" action="{{ route('settings.monitoring') }}">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-5">
                        {{-- Fréquence de vérification --}}
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2">
                                Fréquence de vérification automatique
                            </label>
                            <div class="space-y-2">
                                @foreach(['hourly' => 'Toutes les heures', 'daily' => 'Quotidienne (recommandé)', 'weekly' => 'Hebdomadaire'] as $value => $label)
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" name="check_frequency" value="{{ $value }}"
                                            {{ $user->check_frequency === $value ? 'checked' : '' }}
                                            class="text-brand-600 focus:ring-brand-500">
                                        <span class="text-sm text-neutral-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Timeout HTTP --}}
                        <div>
                            <label for="http_timeout" class="block text-sm font-medium text-neutral-700 mb-1">
                                Timeout HTTP (secondes)
                            </label>
                            <input type="number" id="http_timeout" name="http_timeout"
                                value="{{ old('http_timeout', $user->http_timeout ?? 30) }}"
                                min="5" max="120"
                                class="block w-32 px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            <p class="mt-1 text-xs text-neutral-500">Entre 5 et 120 secondes. Défaut : 30s.</p>
                        </div>

                        {{-- Notifications email --}}
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="email_alerts_enabled" value="0">
                            <input type="checkbox" id="email_alerts_enabled" name="email_alerts_enabled" value="1"
                                {{ ($user->email_alerts_enabled ?? true) ? 'checked' : '' }}
                                class="rounded text-brand-600 focus:ring-brand-500">
                            <label for="email_alerts_enabled" class="text-sm text-neutral-700">
                                Recevoir les emails pour les alertes critiques
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-button type="submit" variant="primary">
                            Sauvegarder
                        </x-button>
                    </div>
                </form>
            </div>

        </div>

        {{-- Onglet APIs SEO --}}
        <div x-show="activeTab === 'seo'" x-cloak>
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl" x-data="seoSettingsForm()">
                <h2 class="text-base font-semibold text-neutral-900 mb-2">Configuration des APIs SEO</h2>
                <p class="text-sm text-neutral-500 mb-6">
                    Connectez une API SEO pour enrichir vos backlinks avec des métriques de qualité (DA, Spam Score…).
                </p>

                <form method="POST" action="{{ route('settings.seo') }}">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-5">
                        {{-- Sélection du provider --}}
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2">Provider SEO</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="seo_provider" value="custom"
                                        x-model="provider"
                                        {{ ($user->seo_provider ?? 'custom') === 'custom' ? 'checked' : '' }}
                                        class="text-brand-600 focus:ring-brand-500">
                                    <div>
                                        <span class="text-sm font-medium text-neutral-700">Aucun (mode gratuit)</span>
                                        <p class="text-xs text-neutral-500">Les métriques ne seront pas disponibles.</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="seo_provider" value="moz"
                                        x-model="provider"
                                        {{ ($user->seo_provider ?? 'custom') === 'moz' ? 'checked' : '' }}
                                        class="text-brand-600 focus:ring-brand-500">
                                    <div>
                                        <span class="text-sm font-medium text-neutral-700">Moz API v2</span>
                                        <p class="text-xs text-neutral-500">Domain Authority, Spam Score.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Clé API (visible seulement si Moz sélectionné) --}}
                        <div x-show="provider === 'moz'" x-transition>
                            <label for="seo_api_key" class="block text-sm font-medium text-neutral-700 mb-1">
                                Clé API Moz (format: accessId:secretKey)
                            </label>
                            <div class="flex gap-2">
                                <input :type="showKey ? 'text' : 'password'"
                                    id="seo_api_key" name="seo_api_key"
                                    placeholder="{{ $user->seo_api_key_encrypted ? '••••••••••••••••' : 'mozscape-XXXX:XXXX' }}"
                                    class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm font-mono">
                                <button type="button" @click="showKey = !showKey"
                                    class="px-3 py-2 border border-neutral-300 rounded-lg text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 text-sm">
                                    <span x-text="showKey ? 'Masquer' : 'Afficher'"></span>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-neutral-500">
                                Trouvez vos clés sur
                                <a href="https://moz.com/api" target="_blank" class="text-brand-600 hover:underline">moz.com/api</a>.
                                Laissez vide pour conserver la clé actuelle.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-button type="submit" variant="primary">Sauvegarder</x-button>

                        <button type="button" @click="testConnection()"
                            x-show="provider === 'moz'"
                            class="px-4 py-2 border border-neutral-300 rounded-lg text-sm text-neutral-700 hover:bg-neutral-50 transition-colors">
                            Tester la connexion
                        </button>
                    </div>

                    {{-- Résultat du test --}}
                    <div x-show="testResult" x-transition class="mt-4 p-3 rounded-lg text-sm"
                        :class="testSuccess ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                        <span x-text="testResult"></span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Onglet DataforSEO --}}
        <div x-show="activeTab === 'dataforseo'" x-cloak>
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl" x-data="dataforSeoForm()">
                <h2 class="text-base font-semibold text-neutral-900 mb-1">DataforSEO API</h2>
                <p class="text-sm text-neutral-500 mb-2">
                    Connectez DataforSEO pour enrichir vos domaines sources avec DA, DR, Spam Score et Referring Domains.
                </p>

                {{-- Indicateur provider actif --}}
                <div class="mb-5 flex items-center gap-2">
                    <span class="text-xs text-neutral-500">Provider actif :</span>
                    @if(($user->seo_provider ?? 'custom') === 'dataforseo')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            ✓ DataforSEO actif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-500">
                            {{ $user->seo_provider ?? 'custom' }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('settings.dataforseo') }}">
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="seo_provider" value="dataforseo">

                    <div class="space-y-4">
                        <div>
                            <label for="dataforseo_login" class="block text-sm font-medium text-neutral-700 mb-1">
                                Login (email)
                            </label>
                            <input :type="showLogin ? 'text' : 'password'"
                                id="dataforseo_login" name="dataforseo_login"
                                placeholder="{{ $user->dataforseo_login_encrypted ? '••••••••••••••••' : 'votre@email.com' }}"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            <p class="mt-1 text-xs text-neutral-500">Laissez vide pour conserver le login actuel.</p>
                        </div>
                        <div>
                            <label for="dataforseo_password" class="block text-sm font-medium text-neutral-700 mb-1">
                                Password
                            </label>
                            <div class="flex gap-2">
                                <input :type="showPassword ? 'text' : 'password'"
                                    id="dataforseo_password" name="dataforseo_password"
                                    placeholder="{{ $user->dataforseo_password_encrypted ? '••••••••' : 'Votre mot de passe DataforSEO' }}"
                                    class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="px-3 py-2 border border-neutral-300 rounded-lg text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 text-sm">
                                    <span x-text="showPassword ? 'Masquer' : 'Afficher'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-button type="submit" variant="primary">Sauvegarder et activer DataforSEO</x-button>
                        <button type="button" @click="testConnection()"
                            class="px-4 py-2 border border-neutral-300 rounded-lg text-sm text-neutral-700 hover:bg-neutral-50 transition-colors">
                            Tester la connexion
                        </button>
                    </div>

                    {{-- Résultat du test --}}
                    <div x-show="testResult" x-transition class="mt-4 p-3 rounded-lg text-sm"
                        :class="testSuccess ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                        <span x-text="testResult"></span>
                    </div>
                </form>

                {{-- Option revenir à custom --}}
                @if(($user->seo_provider ?? 'custom') === 'dataforseo')
                    <div class="mt-6 pt-5 border-t border-neutral-100">
                        <form method="POST" action="{{ route('settings.seo') }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="seo_provider" value="custom">
                            <button type="submit" class="text-xs text-neutral-500 hover:text-red-500 transition-colors">
                                Désactiver DataforSEO (repasser en mode gratuit)
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Onglet Indexation (EPIC-015) --}}
        <div x-show="activeTab === 'indexation'" x-cloak
             x-data="{
                 activeProvider: '{{ $user->indexation_provider ?? 'speedyindex' }}',
                 showKeys: { speedyindex: false, omegaindexer: false, rocketindexer: false, ralfyindex: false },
                 testResult: null, testSuccess: false,
                 async testConnection() {
                     this.testResult = 'Test en cours…';
                     this.testSuccess = false;
                     try {
                         const r = await fetch('{{ route('settings.indexation.test') }}', {
                             method: 'POST',
                             headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                         });
                         const d = await r.json();
                         this.testSuccess = d.success;
                         this.testResult = d.message;
                     } catch (e) {
                         this.testResult = 'Erreur de connexion.';
                     }
                 }
             }">
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl">
                <h2 class="text-base font-semibold text-neutral-900 mb-1">Providers de réindexation</h2>
                <p class="text-sm text-neutral-500 mb-6">
                    Configurez vos clés API pour soumettre vos backlinks aux services de réindexation.
                    Le provider actif sera utilisé pour toutes les nouvelles campagnes.
                </p>

                <form method="POST" action="{{ route('settings.indexation') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    {{-- Sélecteur provider actif --}}
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-3">Provider actif</label>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach(['speedyindex' => 'SpeedyIndex', 'omegaindexer' => 'OmegaIndexer', 'rocketindexer' => 'RocketIndexer', 'ralfyindex' => 'RalfyIndex'] as $slug => $label)
                                <label @click="activeProvider = '{{ $slug }}'"
                                    :class="activeProvider === '{{ $slug }}' ? 'border-brand-500 bg-brand-50' : 'border-neutral-200 hover:border-neutral-300'"
                                    class="relative flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors">
                                    <input type="radio" name="indexation_provider" value="{{ $slug }}"
                                        x-model="activeProvider"
                                        class="sr-only">
                                    <div :class="activeProvider === '{{ $slug }}' ? 'border-brand-500 bg-brand-500' : 'border-neutral-300'"
                                        class="w-3.5 h-3.5 rounded-full border-2 flex-shrink-0 transition-colors"></div>
                                    <span class="text-sm font-medium text-neutral-900">{{ $label }}</span>
                                    @if(! empty($user->{"{$slug}_api_key_encrypted"}))
                                        <span class="ml-auto text-xs text-green-600 font-medium">✓ Configuré</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Clés API --}}
                    @foreach(['speedyindex' => 'SpeedyIndex', 'omegaindexer' => 'OmegaIndexer', 'rocketindexer' => 'RocketIndexer', 'ralfyindex' => 'RalfyIndex'] as $slug => $label)
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1.5">
                                Clé API {{ $label }}
                            </label>
                            <div class="flex gap-2">
                                <input :type="showKeys['{{ $slug }}'] ? 'text' : 'password'"
                                    name="{{ $slug }}_api_key"
                                    placeholder="{{ ! empty($user->{"{$slug}_api_key_encrypted"}) ? '••••••••' : 'Entrez votre clé API' }}"
                                    class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm font-mono">
                                <button type="button" @click="showKeys['{{ $slug }}'] = !showKeys['{{ $slug }}']"
                                    class="px-3 py-2 border border-neutral-300 rounded-lg text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 text-sm">
                                    <span x-text="showKeys['{{ $slug }}'] ? 'Masquer' : 'Afficher'"></span>
                                </button>
                            </div>
                            @if(! empty($user->{"{$slug}_api_key_encrypted"}))
                                <p class="mt-1 text-xs text-neutral-400">Clé déjà configurée — laissez vide pour conserver l'actuelle.</p>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex items-center gap-3">
                        <x-button type="submit" variant="primary">Sauvegarder</x-button>
                        <button type="button" @click="testConnection()"
                            class="px-4 py-2 border border-neutral-300 rounded-lg text-sm text-neutral-700 hover:bg-neutral-50 transition-colors">
                            Tester le provider actif
                        </button>
                        <a href="{{ route('indexation.index') }}"
                            class="px-4 py-2 text-sm text-brand-600 hover:text-brand-700 transition-colors">
                            → Aller aux campagnes
                        </a>
                    </div>

                    {{-- Résultat du test --}}
                    <div x-show="testResult" x-transition class="p-3 rounded-lg text-sm"
                        :class="testSuccess ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                        <span x-text="testResult"></span>
                    </div>

                    {{-- Note crédits DataForSEO --}}
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                        <strong>Note :</strong> La vérification d'indexation via DataForSEO SERP consomme des crédits
                        (1 crédit/URL, check unique à 7 jours). Une campagne de 100 URLs ≈ 100 crédits SERP.
                    </div>
                </form>
            </div>
        </div>

        {{-- Onglet Compte --}}
        <div x-show="activeTab === 'account'" x-cloak>
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Informations du compte</h2>

                <div class="space-y-4 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-neutral-500 w-24">Nom</span>
                        <span class="font-medium text-neutral-900">{{ $user->name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-neutral-500 w-24">Email</span>
                        <span class="font-medium text-neutral-900">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-neutral-500 w-24">Membre depuis</span>
                        <span class="font-medium text-neutral-900">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-neutral-200">
                    <form method="POST" action="/api/v1/auth/logout">
                        @csrf
                        <x-button type="submit" variant="secondary">Se déconnecter</x-button>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
function dataforSeoForm() {
    return {
        showLogin: false,
        showPassword: false,
        testResult: null,
        testSuccess: false,
        async testConnection() {
            this.testResult = 'Test en cours…';
            this.testSuccess = false;
            try {
                const res = await fetch('{{ route('settings.dataforseo.test') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await res.json();
                this.testResult = data.message;
                this.testSuccess = data.success;
            } catch (e) {
                this.testResult = 'Erreur de connexion.';
            }
        },
    };
}

function seoSettingsForm() {
    return {
        provider: '{{ $user->seo_provider ?? 'custom' }}',
        showKey: false,
        testResult: null,
        testSuccess: false,
        async testConnection() {
            this.testResult = 'Test en cours…';
            this.testSuccess = false;
            try {
                const res = await fetch('{{ route('settings.seo.test') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await res.json();
                this.testResult = data.message;
                this.testSuccess = data.success;
            } catch (e) {
                this.testResult = 'Erreur de connexion.';
            }
        },
    };
}
</script>
@endpush
