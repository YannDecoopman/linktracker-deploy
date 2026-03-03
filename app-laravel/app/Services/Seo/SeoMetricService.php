<?php

namespace App\Services\Seo;

use App\Models\DomainMetric;
use App\Services\Seo\Providers\CustomProvider;
use App\Services\Seo\Providers\DataForSeoProvider;
use App\Services\Seo\Providers\MozProvider;
use App\Services\Seo\Providers\SeoMetricProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Service principal pour la récupération des métriques SEO.
 * Résout le provider actif selon la configuration et met à jour le modèle DomainMetric.
 */
class SeoMetricService
{
    private SeoMetricProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    /**
     * Récupère et persiste les métriques SEO pour un domaine.
     */
    public function fetchAndStore(string $domain): DomainMetricsDTO
    {
        $metrics = $this->provider->getMetrics($domain);

        // Met à jour le modèle même en cas d'erreur (pour dater la tentative)
        $record = DomainMetric::forDomain($domain);
        $record->fill($metrics->toArray())->save();

        if ($metrics->hasError()) {
            Log::info('SeoMetricService: provider returned error', [
                'domain'   => $domain,
                'provider' => $this->provider->getName(),
                'error'    => $metrics->error,
            ]);
        } else {
            Log::info('SeoMetricService: metrics updated', [
                'domain'   => $domain,
                'provider' => $this->provider->getName(),
                'da'       => $metrics->da,
                'dr'       => $metrics->dr,
            ]);
        }

        return $metrics;
    }

    /**
     * Retourne le nom du provider actif.
     */
    public function getProviderName(): string
    {
        return $this->provider->getName();
    }

    /**
     * Vérifie si un provider réel (non-custom) est configuré.
     */
    public function hasRealProvider(): bool
    {
        return $this->provider->getName() !== 'custom';
    }

    /**
     * Résout le provider selon la configuration.
     */
    private function resolveProvider(): SeoMetricProviderInterface
    {
        $providerName = config('seo.provider', 'custom');

        return match ($providerName) {
            'moz'        => $this->buildMozProvider(),
            'dataforseo' => $this->buildDataForSeoProvider(),
            default      => new CustomProvider(),
        };
    }

    private function buildDataForSeoProvider(): SeoMetricProviderInterface
    {
        // Préférence : credentials chiffrés en base (utilisateur courant ou premier admin)
        $login    = '';
        $password = '';

        $user = auth()->user() ?? \App\Models\User::first();
        if ($user && ! empty($user->dataforseo_login_encrypted)) {
            try {
                $login    = \Illuminate\Support\Facades\Crypt::decryptString($user->dataforseo_login_encrypted);
                $password = \Illuminate\Support\Facades\Crypt::decryptString($user->dataforseo_password_encrypted ?? '');
            } catch (\Exception) {
                // Fallback sur env si déchiffrement échoue
                $login    = config('seo.dataforseo_login', '');
                $password = config('seo.dataforseo_password', '');
            }
        } else {
            $login    = config('seo.dataforseo_login', '');
            $password = config('seo.dataforseo_password', '');
        }

        $provider = new DataForSeoProvider($login, $password);

        if (! $provider->isAvailable()) {
            Log::warning('SeoMetricService: DataforSEO provider configured but credentials missing, falling back to custom');
            return new CustomProvider();
        }

        return $provider;
    }

    private function buildMozProvider(): SeoMetricProviderInterface
    {
        $accessId  = config('seo.moz_access_id', '');
        $secretKey = config('seo.moz_secret_key', '');

        $provider = new MozProvider($accessId, $secretKey);

        if (! $provider->isAvailable()) {
            Log::warning('SeoMetricService: Moz provider configured but API credentials missing, falling back to custom');
            return new CustomProvider();
        }

        return $provider;
    }
}
