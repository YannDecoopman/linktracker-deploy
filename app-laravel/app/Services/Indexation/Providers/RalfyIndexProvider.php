<?php

namespace App\Services\Indexation\Providers;

use App\Services\Indexation\SubmitResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RalfyIndexProvider implements ReindexingProviderInterface
{
    // TODO: renseigner l'endpoint officiel RalfyIndex
    private const API_BASE  = 'https://api.ralfyindex.com';
    private const MAX_BATCH = 100;

    public function __construct(
        private readonly string $apiKey,
        private readonly int    $timeoutSeconds = 30,
    ) {}

    public function getName(): string
    {
        return 'ralfyindex';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getMaxBatchSize(): int
    {
        return self::MAX_BATCH;
    }

    public function submitUrls(array $urls): SubmitResult
    {
        // TODO: implémenter l'appel API RalfyIndex
        Log::warning('RalfyIndexProvider: not yet implemented');

        return new SubmitResult(
            provider: $this->getName(),
            success: false,
            submitted: 0,
            error: 'Provider not yet implemented.',
        );
    }
}
