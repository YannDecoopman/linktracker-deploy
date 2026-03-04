<?php

namespace App\Jobs;

use App\Models\IndexationCampaign;
use App\Services\Indexation\ReindexingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitIndexationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly IndexationCampaign $campaign
    ) {
        $this->onQueue('default');
    }

    public function handle(ReindexingService $service): void
    {
        $this->campaign->update(['status' => 'running']);

        Log::info('SubmitIndexationJob: starting', [
            'campaign_id' => $this->campaign->id,
            'provider'    => $service->getProviderName(),
        ]);

        // Récupère uniquement les soumissions encore en attente (protection contre double retry)
        $submissions = $this->campaign->submissions()
            ->where('submission_status', 'pending')
            ->get();

        if ($submissions->isEmpty()) {
            $this->campaign->update(['status' => 'completed']);
            return;
        }

        $urls    = array_unique($submissions->pluck('source_url')->all());
        $results = $service->submitInBatches($urls);

        $submitted = 0;
        $failed    = 0;

        foreach ($results as $result) {
            if ($result->success) {
                $submitted += $result->submitted;
                $this->campaign->submissions()
                    ->whereIn('source_url', $result->accepted_urls)
                    ->where('submission_status', 'pending')
                    ->update([
                        'submission_status' => 'submitted',
                        'submitted_at'      => now(),
                        'provider_task_id'  => $result->task_id,
                        'provider_response' => $result->raw_response,
                    ]);
            } else {
                $rejectedUrls = ! empty($result->rejected_urls) ? $result->rejected_urls : $urls;
                $failed += count($rejectedUrls);
                $this->campaign->submissions()
                    ->whereIn('source_url', $rejectedUrls)
                    ->where('submission_status', 'pending')
                    ->update([
                        'submission_status' => 'submit_error',
                        'error_message'     => $result->error,
                    ]);
            }
        }

        $status = match (true) {
            $failed === 0    => 'completed',
            $submitted === 0 => 'failed',
            default          => 'partial',
        };

        $this->campaign->update([
            'status'          => $status,
            'submitted_count' => $submitted,
            'failed_count'    => $failed,
            'submitted_at'    => now(),
        ]);

        // Dispatch les 3 vérifications différées pour les soumissions réussies
        if ($submitted > 0) {
            CheckIndexationJob::dispatch($this->campaign, '24h')->delay(now()->addHours(24));
            CheckIndexationJob::dispatch($this->campaign, '48h')->delay(now()->addHours(48));
            CheckIndexationJob::dispatch($this->campaign, '7d')->delay(now()->addDays(7));

            Log::info('SubmitIndexationJob: checks scheduled', [
                'campaign_id' => $this->campaign->id,
                'submitted'   => $submitted,
            ]);
        }

        Log::info('SubmitIndexationJob: completed', [
            'campaign_id' => $this->campaign->id,
            'status'      => $status,
            'submitted'   => $submitted,
            'failed'      => $failed,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->campaign->update(['status' => 'failed']);

        Log::error('SubmitIndexationJob: failed after retries', [
            'campaign_id' => $this->campaign->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
