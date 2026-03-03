<?php

namespace App\Services\Seo\Providers;

use App\Services\Seo\DomainMetricsDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * STORY-064 : Provider DataforSEO — Domain Authority + Backlinks Summary.
 * Authentification : HTTP Basic (login:password).
 */
class DataForSeoProvider implements SeoMetricProviderInterface
{
    private const API_BASE = 'https://api.dataforseo.com/v3';

    public function __construct(
        private readonly string $login,
        private readonly string $password,
        private readonly int $timeoutSeconds = 20,
    ) {}

    public function getName(): string
    {
        return 'dataforseo';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->login) && ! empty($this->password);
    }

    public function getMetrics(string $domain): DomainMetricsDTO
    {
        try {
            [$da, $spam] = $this->fetchDomainAuthority($domain);
            [$dr, $referring, $keywords] = $this->fetchBacklinksSummary($domain);

            return new DomainMetricsDTO(
                domain: $domain,
                da: $da,
                dr: $dr,
                spam_score: $spam,
                referring_domains_count: $referring,
                organic_keywords_count: $keywords,
                provider: $this->getName(),
            );

        } catch (\Exception $e) {
            Log::error('DataForSeoProvider: unexpected error', [
                'domain' => $domain,
                'error'  => $e->getMessage(),
            ]);

            return new DomainMetricsDTO(
                domain: $domain,
                provider: $this->getName(),
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Récupère DA et spam score via DataforSEO Domain Authority.
     */
    private function fetchDomainAuthority(string $domain): array
    {
        $response = Http::withBasicAuth($this->login, $this->password)
            ->timeout($this->timeoutSeconds)
            ->post(self::API_BASE . '/dataforseo_labs/google/domain_rank_overview/live', [
                [
                    'target'           => $domain,
                    'location_code'    => 2250, // France
                    'language_code'    => 'fr',
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('DataForSeoProvider: domain_rank_overview failed', [
                'domain' => $domain,
                'status' => $response->status(),
            ]);
            return [null, null];
        }

        $item = $response->json('tasks.0.result.0.items.0') ?? [];

        $da   = isset($item['rank']) ? (int) $item['rank'] : null;
        $spam = isset($item['spam_score']) ? (int) ($item['spam_score'] * 100) : null;

        return [$da, $spam];
    }

    /**
     * Récupère DR, referring domains et organic keywords via Backlinks Summary.
     */
    private function fetchBacklinksSummary(string $domain): array
    {
        $response = Http::withBasicAuth($this->login, $this->password)
            ->timeout($this->timeoutSeconds)
            ->post(self::API_BASE . '/backlinks/domain_pages_summary/live', [
                [
                    'target'      => $domain,
                    'target_type' => 'domain',
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('DataForSeoProvider: backlinks_summary failed', [
                'domain' => $domain,
                'status' => $response->status(),
            ]);
            return [null, null, null];
        }

        $item = $response->json('tasks.0.result.0') ?? [];

        $dr        = isset($item['domain_from_rank']) ? (int) $item['domain_from_rank'] : null;
        $referring = isset($item['referring_domains']) ? (int) $item['referring_domains'] : null;

        // Organic keywords via domain_rank_overview si disponible
        $keywords = null;

        return [$dr, $referring, $keywords];
    }
}
