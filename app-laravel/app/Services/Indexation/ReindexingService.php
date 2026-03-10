<?php

namespace App\Services\Indexation;

use App\Services\Indexation\Providers\OmegaIndexerProvider;
use App\Services\Indexation\Providers\RalfyIndexProvider;
use App\Services\Indexation\Providers\ReindexingProviderInterface;
use App\Services\Indexation\Providers\RocketIndexerProvider;
use App\Services\Indexation\Providers\SpeedyIndexProvider;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ReindexingService
{
    private ReindexingProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    public function getProvider(): ReindexingProviderInterface
    {
        return $this->provider;
    }

    public function getProviderName(): string
    {
        return $this->provider->getName();
    }

    public function isConfigured(): bool
    {
        return $this->provider->isAvailable();
    }

    /**
     * Soumet des URLs en lots en respectant la limite du provider.
     *
     * @param  array<string> $urls
     * @return array<SubmitResult>
     */
    public function submitInBatches(array $urls): array
    {
        $batchSize = $this->provider->getMaxBatchSize();
        $chunks    = array_chunk($urls, $batchSize);
        $results   = [];

        foreach ($chunks as $chunk) {
            $results[] = $this->provider->submitUrls($chunk);
        }

        return $results;
    }

    private function resolveProvider(): ReindexingProviderInterface
    {
        $user         = auth()->user();
        $providerName = $user?->indexation_provider ?? 'speedyindex';

        return match ($providerName) {
            'speedyindex'   => $this->buildSpeedyIndex($user),
            'omegaindexer'  => $this->buildOmegaIndexer($user),
            'rocketindexer' => $this->buildRocketIndexer($user),
            'ralfyindex'    => $this->buildRalfyIndex($user),
            default         => $this->buildSpeedyIndex($user),
        };
    }

    private function buildSpeedyIndex($user): ReindexingProviderInterface
    {
        $key      = $this->decryptKey($user, 'speedyindex_api_key_encrypted');
        $provider = new SpeedyIndexProvider($key);

        if (! $provider->isAvailable()) {
            Log::warning('ReindexingService: SpeedyIndex API key missing');
        }

        return $provider;
    }

    private function buildOmegaIndexer($user): ReindexingProviderInterface
    {
        $key      = $this->decryptKey($user, 'omegaindexer_api_key_encrypted');
        $provider = new OmegaIndexerProvider($key);

        if (! $provider->isAvailable()) {
            Log::warning('ReindexingService: OmegaIndexer API key missing');
        }

        return $provider;
    }

    private function buildRocketIndexer($user): ReindexingProviderInterface
    {
        $key      = $this->decryptKey($user, 'rocketindexer_api_key_encrypted');
        $provider = new RocketIndexerProvider($key);

        if (! $provider->isAvailable()) {
            Log::warning('ReindexingService: RocketIndexer API key missing');
        }

        return $provider;
    }

    private function buildRalfyIndex($user): ReindexingProviderInterface
    {
        $key      = $this->decryptKey($user, 'ralfyindex_api_key_encrypted');
        $provider = new RalfyIndexProvider($key);

        if (! $provider->isAvailable()) {
            Log::warning('ReindexingService: RalfyIndex API key missing');
        }

        return $provider;
    }

    private function decryptKey($user, string $column): string
    {
        if ($user && ! empty($user->{$column})) {
            try {
                return Crypt::decryptString($user->{$column});
            } catch (\Exception) {
                // Clé corrompue, on retourne vide
            }
        }

        return '';
    }
}
