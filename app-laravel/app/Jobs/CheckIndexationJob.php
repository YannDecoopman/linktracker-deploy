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

    /**
     * @param  string $checkPhase  '24h' | '48h' | '7d'
     */
    public function __construct(
        public readonly IndexationCampaign $campaign,
        public readonly string             $checkPhase,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $checkedAtColumn = match ($this->checkPhase) {
            '24h'   => 'check_24h_at',
            '48h'   => 'check_48h_at',
            '7d'    => 'check_7d_at',
            default => throw new \InvalidArgumentException("Phase inconnue : {$this->checkPhase}"),
        };

        Log::info('CheckIndexationJob: starting', [
            'campaign_id' => $this->campaign->id,
            'phase'       => $this->checkPhase,
        ]);

        // Récupère les soumissions à vérifier pour cette phase
        $submissions = $this->campaign->submissions()
            ->where('submission_status', 'submitted')
            ->whereNull($checkedAtColumn)
            ->get();

        if ($submissions->isEmpty()) {
            Log::info('CheckIndexationJob: no submissions to check', [
                'campaign_id' => $this->campaign->id,
                'phase'       => $this->checkPhase,
            ]);

            if ($this->checkPhase === '7d') {
                $this->campaign->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
            }

            return;
        }

        $urls       = $submissions->pluck('source_url')->all();
        $results    = $this->checkWithDataForSeo($urls);
        $indexedNow = 0;
        $now        = now();

        foreach ($submissions as $submission) {
            $isIndexed  = $results[$submission->source_url] ?? null;
            $updateData = [
                $checkedAtColumn    => $now,
                'last_checked_at'   => $now,
                'last_check_result' => $isIndexed,
            ];

            if ($isIndexed === true && is_null($submission->indexed_at)) {
                // Première confirmation positive → mise à jour du backlink
                $updateData['indexed_at']        = $now;
                $updateData['submission_status'] = 'indexed';
                $indexedNow++;

                if ($submission->backlink_id) {
                    Backlink::where('id', $submission->backlink_id)
                        ->update(['is_indexed' => true]);
                }
            } elseif ($isIndexed === false && $this->checkPhase === '7d') {
                // Après 7 jours, toujours pas indexé → statut final
                $updateData['submission_status'] = 'not_indexed';
            }
            // null = erreur check DataForSEO → on garde 'submitted' pour ne pas bloquer les phases suivantes

            $submission->update($updateData);
        }

        // Recalcule le compteur total indexé depuis la DB
        $totalIndexed = $this->campaign->submissions()
            ->where('submission_status', 'indexed')
            ->count();

        $this->campaign->update(['indexed_count' => $totalIndexed]);

        // Clôture de la campagne après le check final
        if ($this->checkPhase === '7d') {
            $this->campaign->update([
                'status'       => 'completed',
                'completed_at' => $now,
            ]);
        }

        Log::info('CheckIndexationJob: done', [
            'campaign_id'  => $this->campaign->id,
            'phase'        => $this->checkPhase,
            'checked'      => count($urls),
            'indexed_now'  => $indexedNow,
            'total_indexed' => $totalIndexed,
        ]);
    }

    /**
     * Vérifie l'indexation Google via DataForSEO SERP "site:url".
     *
     * NOTE : chaque vérification consomme des crédits DataForSEO.
     * Une campagne de N URLs × 3 phases = 3N appels SERP.
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
     * Résout les credentials DataForSEO (user DB → fallback env).
     *
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
            'phase'       => $this->checkPhase,
            'error'       => $e->getMessage(),
        ]);
    }
}
