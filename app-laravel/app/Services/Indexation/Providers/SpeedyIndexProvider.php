<?php

namespace App\Services\Indexation\Providers;

use App\Services\Indexation\SubmitResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpeedyIndexProvider implements ReindexingProviderInterface
{
    private const API_BASE      = 'https://api.speedyindex.com';
    private const MAX_BATCH     = 10000;

    public function __construct(
        private readonly string $apiKey,
        private readonly int    $timeoutSeconds = 30,
    ) {}

    public function getName(): string
    {
        return 'speedyindex';
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
        try {
            $response = Http::withHeaders(['Authorization' => $this->apiKey])
                ->timeout($this->timeoutSeconds)
                ->post(self::API_BASE . '/v2/task/google/indexer/create', [
                    'urls' => array_values($urls),
                ]);

            if (! $response->successful()) {
                return new SubmitResult(
                    provider: $this->getName(),
                    success: false,
                    submitted: 0,
                    error: "HTTP {$response->status()}: " . $response->body(),
                    raw_response: $response->json() ?? [],
                );
            }

            return new SubmitResult(
                provider: $this->getName(),
                success: true,
                submitted: count($urls),
                accepted_urls: $urls,
                task_id: $response->json('task_id'),
                raw_response: $response->json() ?? [],
            );
        } catch (\Exception $e) {
            Log::error('SpeedyIndexProvider: error', ['error' => $e->getMessage()]);

            return new SubmitResult(
                provider: $this->getName(),
                success: false,
                submitted: 0,
                error: $e->getMessage(),
            );
        }
    }
}
