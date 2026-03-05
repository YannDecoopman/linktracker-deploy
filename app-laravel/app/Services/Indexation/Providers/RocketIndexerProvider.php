<?php

namespace App\Services\Indexation\Providers;

use App\Services\Indexation\SubmitResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RocketIndexerProvider implements ReindexingProviderInterface
{
    // TODO: renseigner l'endpoint officiel RocketIndexer
    private const API_BASE  = 'https://api.rocketindexer.com';
    private const MAX_BATCH = 100;

    public function __construct(
        private readonly string $apiKey,
        private readonly int    $timeoutSeconds = 30,
    ) {}

    public function getName(): string
    {
        return 'rocketindexer';
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
        // TODO: implémenter l'appel API RocketIndexer
        Log::warning('RocketIndexerProvider: not yet implemented');

        return new SubmitResult(
            provider: $this->getName(),
            success: false,
            submitted: 0,
            error: 'Provider not yet implemented.',
        );
    }
}
