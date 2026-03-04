<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de non-régression pour app:snapshot-backlinks.
 *
 * Règle fondamentale : la date de référence est published_at (ou created_at si absent).
 * Un backlink ne doit apparaître dans un snapshot que si sa date de publication
 * est antérieure ou égale à la date du snapshot.
 */
class SnapshotBacklinksCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
    }

    // ── Snapshot quotidien ────────────────────────────────────────────────

    public function test_snapshot_counts_only_backlinks_published_before_or_on_snapshot_date(): void
    {
        // Publié il y a 5 jours → doit apparaître
        Backlink::factory()->for($this->project)->create([
            'status'       => 'active',
            'published_at' => today()->subDays(5)->toDateString(),
        ]);

        // Publié demain → ne doit PAS apparaître dans le snapshot d'aujourd'hui
        Backlink::factory()->for($this->project)->create([
            'status'       => 'active',
            'published_at' => today()->addDay()->toDateString(),
        ]);

        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        $snapshot = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today())
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals(1, $snapshot->count_active);
        $this->assertEquals(1, $snapshot->count_total);
    }

    public function test_snapshot_uses_created_at_when_published_at_is_null(): void
    {
        // Sans published_at → created_at sert de proxy
        Backlink::factory()->for($this->project)->create([
            'status'       => 'active',
            'published_at' => null,
        ]);

        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        $snapshot = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today())
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals(1, $snapshot->count_total);
    }

    public function test_snapshot_counts_statuses_correctly(): void
    {
        $pub = today()->subDays(2)->toDateString();

        Backlink::factory()->for($this->project)->create(['status' => 'active',  'published_at' => $pub]);
        Backlink::factory()->for($this->project)->create(['status' => 'active',  'published_at' => $pub]);
        Backlink::factory()->for($this->project)->create(['status' => 'lost',    'published_at' => $pub]);
        Backlink::factory()->for($this->project)->create(['status' => 'changed', 'published_at' => $pub]);

        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        $snapshot = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today())
            ->first();

        $this->assertEquals(2, $snapshot->count_active);
        $this->assertEquals(1, $snapshot->count_lost);
        $this->assertEquals(1, $snapshot->count_changed);
        $this->assertEquals(4, $snapshot->count_total);
    }

    public function test_snapshot_generates_per_project_snapshot(): void
    {
        $pub = today()->subDay()->toDateString();

        Backlink::factory()->for($this->project)->create(['status' => 'active', 'published_at' => $pub]);
        Backlink::factory()->for($this->project)->create(['status' => 'lost',   'published_at' => $pub]);

        $otherProject = Project::factory()->for($this->user)->create();
        Backlink::factory()->for($otherProject)->create(['status' => 'active', 'published_at' => $pub]);

        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        // Snapshot global : 3 backlinks (2 project + 1 otherProject)
        $global = BacklinkSnapshot::whereNull('project_id')->whereDate('snapshot_date', today())->first();
        $this->assertEquals(3, $global->count_total);

        // Snapshot par projet : seulement les 2 du project
        $perProject = BacklinkSnapshot::where('project_id', $this->project->id)->whereDate('snapshot_date', today())->first();
        $this->assertNotNull($perProject);
        $this->assertEquals(1, $perProject->count_active);
        $this->assertEquals(1, $perProject->count_lost);
        $this->assertEquals(2, $perProject->count_total);
    }

    public function test_snapshot_replaces_existing_snapshot_for_same_date(): void
    {
        $pub = today()->subDay()->toDateString();

        // Premier snapshot : 1 backlink
        Backlink::factory()->for($this->project)->create(['status' => 'active', 'published_at' => $pub]);
        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        // Deuxième snapshot même jour : 2 backlinks (un ajouté)
        Backlink::factory()->for($this->project)->create(['status' => 'active', 'published_at' => $pub]);
        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        $count = BacklinkSnapshot::whereNull('project_id')->whereDate('snapshot_date', today())->count();
        $this->assertEquals(1, $count, 'Un seul snapshot doit exister par date');

        $snapshot = BacklinkSnapshot::whereNull('project_id')->whereDate('snapshot_date', today())->first();
        $this->assertEquals(2, $snapshot->count_active);
    }

    // ── Rétro-remplissage (--backfill) ────────────────────────────────────

    public function test_backfill_creates_snapshots_for_each_day(): void
    {
        Backlink::factory()->for($this->project)->create([
            'status'       => 'active',
            'published_at' => today()->subDays(3)->toDateString(),
        ]);

        $this->artisan('app:snapshot-backlinks', ['--backfill' => 7])->assertSuccessful();

        // 7 snapshots globaux doivent exister
        $count = BacklinkSnapshot::whereNull('project_id')->count();
        $this->assertEquals(7, $count);
    }

    public function test_backfill_respects_published_at_per_day(): void
    {
        // Publié il y a 5 jours → ne doit pas apparaître dans les snapshots antérieurs
        Backlink::factory()->for($this->project)->create([
            'status'       => 'active',
            'published_at' => today()->subDays(5)->toDateString(),
        ]);

        $this->artisan('app:snapshot-backlinks', ['--backfill' => 7])->assertSuccessful();

        // Snapshot d'il y a 6 jours → 0 backlinks (publié uniquement il y a 5 jours)
        $snapshotBefore = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today()->subDays(6))
            ->first();
        $this->assertNotNull($snapshotBefore);
        $this->assertEquals(0, $snapshotBefore->count_total);

        // Snapshot d'il y a 5 jours → 1 backlink
        $snapshotOnDay = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today()->subDays(5))
            ->first();
        $this->assertNotNull($snapshotOnDay);
        $this->assertEquals(1, $snapshotOnDay->count_total);

        // Snapshot d'aujourd'hui → toujours 1
        $snapshotToday = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today())
            ->first();
        $this->assertEquals(1, $snapshotToday->count_total);
    }

    public function test_backfill_counts_statuses_on_all_days(): void
    {
        $pub = today()->subDays(2)->toDateString();

        Backlink::factory()->for($this->project)->create(['status' => 'active', 'published_at' => $pub]);
        Backlink::factory()->for($this->project)->create(['status' => 'lost',   'published_at' => $pub]);

        $this->artisan('app:snapshot-backlinks', ['--backfill' => 3])->assertSuccessful();

        // Snapshot d'il y a 2 jours : vrais statuts
        $snapshot = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today()->subDays(2))
            ->first();

        $this->assertEquals(1, $snapshot->count_active);
        $this->assertEquals(1, $snapshot->count_lost);
        $this->assertEquals(2, $snapshot->count_total);
    }

    public function test_backfill_and_daily_snapshot_are_consistent(): void
    {
        $pub = today()->subDays(1)->toDateString();

        Backlink::factory()->for($this->project)->create(['status' => 'active', 'published_at' => $pub]);
        Backlink::factory()->for($this->project)->create(['status' => 'lost',   'published_at' => $pub]);

        // Snapshot quotidien
        $this->artisan('app:snapshot-backlinks')->assertSuccessful();

        // Backfill qui écrase aujourd'hui
        $this->artisan('app:snapshot-backlinks', ['--backfill' => 1])->assertSuccessful();

        $snapshot = BacklinkSnapshot::whereNull('project_id')
            ->whereDate('snapshot_date', today())
            ->first();

        // Les deux méthodes doivent donner le même résultat
        $this->assertEquals(1, $snapshot->count_active);
        $this->assertEquals(1, $snapshot->count_lost);
        $this->assertEquals(2, $snapshot->count_total);
    }
}
