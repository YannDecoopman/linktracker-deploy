<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-060 : Bulk actions sur les backlinks (bulkDelete, bulkEdit)
 */
class BacklinkBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    // ── bulkDelete ───────────────────────────────────────────────────────

    public function test_bulk_delete_succeeds_with_valid_ids(): void
    {
        $backlinks = Backlink::factory()->count(3)->for($this->project)->create();
        $ids = $backlinks->pluck('id')->toArray();

        $response = $this->post(route('backlinks.bulk-delete'), ['ids' => $ids]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('backlinks', ['id' => $id]);
        }
    }

    public function test_bulk_delete_returns_count_in_flash(): void
    {
        $backlinks = Backlink::factory()->count(2)->for($this->project)->create();
        $ids = $backlinks->pluck('id')->toArray();

        $response = $this->post(route('backlinks.bulk-delete'), ['ids' => $ids]);

        $response->assertSessionHas('success', '2 backlink(s) supprimé(s).');
    }

    public function test_bulk_delete_fails_with_empty_ids(): void
    {
        $response = $this->post(route('backlinks.bulk-delete'), ['ids' => []]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_delete_fails_with_too_many_ids(): void
    {
        // 501 IDs (max est 500)
        $response = $this->post(route('backlinks.bulk-delete'), [
            'ids' => range(1, 501),
        ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_delete_fails_with_nonexistent_ids(): void
    {
        $response = $this->post(route('backlinks.bulk-delete'), ['ids' => [99999, 99998]]);

        $response->assertSessionHasErrors();
    }

    // ── bulkEdit — published_at ──────────────────────────────────────────

    public function test_bulk_edit_published_at_with_valid_date(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'published_at',
            'value' => '2024-01-15',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('backlinks', [
            'id'           => $backlink->id,
            'published_at' => '2024-01-15',
        ]);
    }

    public function test_bulk_edit_published_at_with_null_clears_value(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create([
            'published_at' => '2024-06-01',
        ]);

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'published_at',
            'value' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'           => $backlink->id,
            'published_at' => null,
        ]);
    }

    // ── bulkEdit — status ────────────────────────────────────────────────

    public function test_bulk_edit_status_to_lost(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create(['status' => 'active']);

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'status',
            'value' => 'lost',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'     => $backlink->id,
            'status' => 'lost',
        ]);
    }

    public function test_bulk_edit_status_to_active(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create(['status' => 'lost']);

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'status',
            'value' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'     => $backlink->id,
            'status' => 'active',
        ]);
    }

    public function test_bulk_edit_status_invalid_value_returns_error(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'status',
            'value' => 'invalide',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('field');
    }

    // ── bulkEdit — is_indexed ────────────────────────────────────────────

    public function test_bulk_edit_is_indexed_to_true(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create(['is_indexed' => false]);

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'is_indexed',
            'value' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'         => $backlink->id,
            'is_indexed' => 1,
        ]);
    }

    public function test_bulk_edit_is_indexed_to_false(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create(['is_indexed' => true]);

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'is_indexed',
            'value' => '0',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'         => $backlink->id,
            'is_indexed' => 0,
        ]);
    }

    public function test_bulk_edit_is_indexed_to_null(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create(['is_indexed' => true]);

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'is_indexed',
            'value' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'         => $backlink->id,
            'is_indexed' => null,
        ]);
    }

    // ── bulkEdit — is_dofollow ───────────────────────────────────────────

    public function test_bulk_edit_is_dofollow_to_true(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'is_dofollow',
            'value' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'          => $backlink->id,
            'is_dofollow' => 1,
        ]);
    }

    public function test_bulk_edit_is_dofollow_to_false(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'is_dofollow',
            'value' => '0',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backlinks', [
            'id'          => $backlink->id,
            'is_dofollow' => 0,
        ]);
    }

    // ── bulkEdit — validations générales ────────────────────────────────

    public function test_bulk_edit_fails_with_invalid_field(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [$backlink->id],
            'field' => 'couleur',
            'value' => 'rouge',
        ]);

        $response->assertSessionHasErrors('field');
    }

    public function test_bulk_edit_fails_with_empty_ids(): void
    {
        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => [],
            'field' => 'status',
            'value' => 'active',
        ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_edit_updates_multiple_backlinks(): void
    {
        $backlinks = Backlink::factory()->count(3)->for($this->project)->create(['status' => 'active']);
        $ids = $backlinks->pluck('id')->toArray();

        $response = $this->post(route('backlinks.bulk-edit'), [
            'ids'   => $ids,
            'field' => 'status',
            'value' => 'changed',
        ]);

        $response->assertSessionHas('success', '3 backlink(s) mis à jour.');
        foreach ($ids as $id) {
            $this->assertDatabaseHas('backlinks', ['id' => $id, 'status' => 'changed']);
        }
    }
}
