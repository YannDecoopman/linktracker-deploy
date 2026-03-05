<?php

namespace App\Jobs;

use App\Models\Backlink;
use App\Models\IndexationCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckIndexationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly IndexationCampaign $campaign,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('CheckIndexationJob: starting', [
            'campaign_id' => $this->campaign->id,
        ]);

        $submissions = $this->campaign->submissions()
            ->where('submission_status', 'submitted')
            ->whereNull('check_7d_at')
            ->get();

        if ($submissions->isEmpty()) {
            Log::info('CheckIndexationJob: no submissions to check', [
                'campaign_id' => $this->campaign->id,
            ]);

            $this->campaign->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            return;
        }

        $urls       = $submissions->pluck('source_url')->all();
        $results    = $this->checkWithDataForSeo($urls);
        $indexedNow = 0;
        $now        = now();

        foreach ($submissions as $submission) {
            $isIndexed  = $results[$submission->source_url] ?? null;
            $updateData = [
                'check_7d_at'       => $now,
                'last_checked_at'   => $now,
                'last_check_result' => $isIndexed,
                'submission_status' => $isIndexed === true ? 'indexed' : 'not_indexed',
            ];

            if ($isIndexed === true && is_null($submission->indexed_at)) {
                $updateData['indexed_at'] = $now;
                $indexedNow++;

                if ($submission->backlink_id) {
                    Backlink::where('id', $submission->backlink_id)
                        ->update(['is_indexed' => true]);
                }
            }

            // null = erreur DataForSEO → on considère not_indexed mais on ne bloque pas
            $submission->update($updateData);
        }

        $totalIndexed = $this->campaign->submissions()
            ->where('submission_status', 'indexed')
            ->count();

        $this->campaign->update([
            'indexed_count' => $totalIndexed,
            'status'        => 'completed',
            'completed_at'  => $now,
        ]);

        Log::info('CheckIndexationJob: done', [
            'campaign_id'   => $this->campaign->id,
            'checked'       => count($urls),
            'indexed_now'   => $indexedNow,
            'total_indexed' => $totalIndexed,
        ]);
    }

    /**
     * Vérifie l'indexation Google via DataForSEO SERP "site:url".
     *
     * @param  array<string> $urls
     * @return array<string, bool|null>  ['url' => true|false|null]
     */
    private function checkWithDataForSeo(array $urls): array
    {
        [$login, $password] = $this->resolveDataForSeoCredentials();

        if (empty($login)) {
            Log::warning('CheckIndexationJob: DataForSEO credentials missing, skipping checks');
            return array_fill_keys($urls, null);
        }

        $results = [];

        foreach ($urls as $url) {
            try {
                $response = Http::withBasicAuth($login, $password)
                    ->timeout(20)
                    ->post('https://api.dataforseo.com/v3/serp/google/organic/live/regular', [
                        [
                            'keyword'       => "site:{$url}",
                            'location_code' => 2840,
                            'language_code' => 'en',
                            'depth'         => 1,
                        ],
                    ]);

                if ($response->successful()) {
                    $items         = $response->json('tasks.0.result.0.items') ?? [];
                    $results[$url] = count($items) > 0;
                } else {
                    $results[$url] = null;
                    Log::warning('CheckIndexationJob: DataForSEO HTTP error', [
                        'url'    => $url,
                        'status' => $response->status(),
                    ]);
                }
            } catch (\Exception $e) {
                $results[$url] = null;
                Log::warning('CheckIndexationJob: DataForSEO request failed', [
                    'url'   => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * @return array{0: string, 1: string}  [login, password]
     */
    private function resolveDataForSeoCredentials(): array
    {
        $user     = \App\Models\User::first();
        $login    = '';
        $password = '';

        if ($user && ! empty($user->dataforseo_login_encrypted)) {
            try {
                $login    = Crypt::decryptString($user->dataforseo_login_encrypted);
                $password = Crypt::decryptString($user->dataforseo_password_encrypted ?? '');
            } catch (\Exception) {
                $login    = config('seo.dataforseo_login', '');
                $password = config('seo.dataforseo_password', '');
            }
        } else {
            $login    = config('seo.dataforseo_login', '');
            $password = config('seo.dataforseo_password', '');
        }

        return [$login, $password];
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CheckIndexationJob: failed after retries', [
            'campaign_id' => $this->campaign->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
