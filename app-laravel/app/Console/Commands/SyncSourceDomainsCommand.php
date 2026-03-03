<?php

namespace App\Console\Commands;

use App\Jobs\FetchSeoMetricsJob;
use App\Models\Backlink;
use App\Models\DomainMetric;
use App\Models\SourceDomain;
use Illuminate\Console\Command;

/**
 * STORY-063 : Synchronisation initiale (batch) de tous les domaines sources
 * depuis les source_url des backlinks existants.
 */
class SyncSourceDomainsCommand extends Command
{
    protected $signature = 'app:sync-source-domains
                            {--fetch-metrics : Dispatcher FetchSeoMetricsJob pour les nouveaux domaines}
                            {--force : Re-synchroniser même les domaines déjà connus}';

    protected $description = 'Synchronise la table source_domains depuis les source_url des backlinks existants';

    public function handle(): int
    {
        $fetchMetrics = $this->option('fetch-metrics');
        $force        = $this->option('force');

        $total   = Backlink::whereNotNull('source_url')->count();
        $created = 0;
        $updated = 0;

        $this->info("Synchronisation de {$total} backlinks…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Backlink::whereNotNull('source_url')
            ->select('source_url')
            ->distinct()
            ->orderBy('source_url')
            ->chunk(200, function ($backlinks) use ($fetchMetrics, $force, &$created, &$updated, $bar) {
                foreach ($backlinks as $backlink) {
                    $sourceDomain = SourceDomain::fromUrl($backlink->source_url);

                    if ($sourceDomain->wasRecentlyCreated) {
                        $created++;
                        if ($fetchMetrics) {
                            $metric = DomainMetric::forDomain($sourceDomain->domain);
                            FetchSeoMetricsJob::dispatch($metric);
                        }
                    } elseif ($force) {
                        $sourceDomain->update(['last_synced_at' => now()]);
                        $updated++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Terminé : {$created} domaine(s) créé(s), {$updated} mis à jour.");

        if ($fetchMetrics && $created > 0) {
            $this->info("{$created} job(s) FetchSeoMetricsJob dispatchés.");
        }

        return self::SUCCESS;
    }
}
