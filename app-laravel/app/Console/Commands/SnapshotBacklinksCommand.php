<?php

namespace App\Console\Commands;

use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotBacklinksCommand extends Command
{
    protected $signature = 'app:snapshot-backlinks
                            {--date= : Date du snapshot (Y-m-d, défaut : aujourd\'hui)}
                            {--backfill=0 : Nombre de jours à rétro-remplir depuis les données existantes}';

    protected $description = 'Enregistre un snapshot quotidien du nombre de backlinks par statut et par projet';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : today()->toDateString();

        $backfillDays = (int) $this->option('backfill');

        if ($backfillDays > 0) {
            return $this->runBackfill($backfillDays);
        }

        $this->takeSnapshot($date);
        $this->info("✅ Snapshot du {$date} enregistré.");

        return self::SUCCESS;
    }

    private function takeSnapshot(string $date): void
    {
        // Snapshot global (toutes projets confondus)
        $this->upsertSnapshot($date, null);

        // Snapshot par projet
        $projectIds = Project::pluck('id');
        foreach ($projectIds as $projectId) {
            $this->upsertSnapshot($date, $projectId);
        }
    }

    private function upsertSnapshot(string $date, ?int $projectId): void
    {
        // Ne compter que les backlinks publiés à cette date (published_at ou created_at comme proxy)
        $query = Backlink::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->where(DB::raw("DATE(COALESCE(published_at, DATE(created_at)))"), '<=', $date);

        BacklinkSnapshot::whereRaw("DATE(snapshot_date) = ?", [$date])
            ->where('project_id', $projectId)
            ->delete();

        BacklinkSnapshot::create([
            'snapshot_date'    => $date,
            'project_id'       => $projectId,
            'count_active'     => (clone $query)->where('status', 'active')->count(),
            'count_lost'       => (clone $query)->where('status', 'lost')->count(),
            'count_changed'    => (clone $query)->where('status', 'changed')->count(),
            'count_total'      => (clone $query)->count(),
            'count_perfect'    => (clone $query)->where('status', 'active')->where('is_indexed', true)->where('is_dofollow', true)->count(),
            'count_not_indexed'=> (clone $query)->where('is_indexed', false)->count(),
            'count_nofollow'   => (clone $query)->where('is_dofollow', false)->count(),
        ]);
    }

    /**
     * Rétro-remplissage : reconstitue les snapshots passés en se basant sur published_at.
     * Chaque jour, on compte les backlinks dont la date de publication est <= ce jour,
     * avec leurs statuts actuels (meilleure approximation disponible sans historique de statuts).
     */
    private function runBackfill(int $days): int
    {
        $this->info("🔄 Rétro-remplissage sur {$days} jours...");

        $projectIds = array_merge([null], Project::pluck('id')->toArray());

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i)->toDateString();

            foreach ($projectIds as $projectId) {
                $this->upsertSnapshot($date, $projectId);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Rétro-remplissage terminé ({$days} jours).");

        return self::SUCCESS;
    }
}
