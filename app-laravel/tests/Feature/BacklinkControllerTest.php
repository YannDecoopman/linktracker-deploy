<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Platform;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test backlink creation with all required fields.
     */
    public function test_can_create_backlink_with_required_fields(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
        ]);

        $response->assertRedirect(route('backlinks.index'));
        $this->assertDatabaseHas('backlinks', [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'status' => 'active',
        ]);
    }

    /**
     * Test backlink creation with all extended fields.
     */
    public function test_can_create_backlink_with_all_extended_fields(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'anchor_text' => 'Test Anchor',
            'tier_level' => 'tier1',
            'spot_type' => 'internal',
            'published_at' => '2024-01-15',
            'expires_at' => '2024-12-31',
            'price' => 150.50,
            'currency' => 'EUR',
            'invoice_paid' => true,
            'platform_id' => $platform->id,
            'contact_info' => 'John Doe - john@example.com',
            'is_dofollow' => true,
        ]);

        $response->assertRedirect(route('backlinks.index'));

        $this->assertDatabaseHas('backlinks', [
            'project_id' => $project->id,
            'tier_level' => 'tier1',
            'spot_type' => 'internal',
            'price' => '150.50',
            'currency' => 'EUR',
            'invoice_paid' => true,
            'platform_id' => $platform->id,
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Test tier2 backlink requires parent_backlink_id.
     */
    public function test_tier2_requires_parent_backlink_id(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier2',
            'spot_type' => 'external',
            // Missing parent_backlink_id
        ]);

        $response->assertSessionHasErrors('parent_backlink_id');
    }

    /**
     * Test tier2 can have parent.
     */
    public function test_tier2_can_have_parent(): void
    {
        $project = Project::factory()->create();
        $parentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://tier2.com/page',
            'target_url' => $parentBacklink->source_url,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
            'spot_type' => 'external',
        ]);

        $response->assertRedirect(route('backlinks.index'));
        $this->assertDatabaseHas('backlinks', [
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
        ]);
    }

    /**
     * Test tier1 cannot have parent_backlink_id.
     */
    public function test_tier1_cannot_have_parent(): void
    {
        $project = Project::factory()->create();
        $parentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'parent_backlink_id' => $parentBacklink->id, // Should fail
            'spot_type' => 'external',
        ]);

        $response->assertSessionHasErrors('parent_backlink_id');
    }

    /**
     * Test parent must be tier1.
     */
    public function test_parent_must_be_tier1(): void
    {
        $project = Project::factory()->create();
        $tier1 = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);
        $tier2 = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $tier1->id,
        ]);

        // Try to create tier2 with another tier2 as parent (should fail)
        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier2',
            'parent_backlink_id' => $tier2->id, // tier2 as parent should fail
            'spot_type' => 'external',
        ]);

        $response->assertSessionHasErrors('parent_backlink_id');
    }

    /**
     * Test price requires currency.
     */
    public function test_price_requires_currency(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'price' => 100.00,
            // Missing currency
        ]);

        $response->assertSessionHasErrors('currency');
    }

    /**
     * Test currency requires price.
     */
    public function test_currency_requires_price(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'currency' => 'EUR',
            // Missing price
        ]);

        $response->assertSessionHasErrors('currency');
    }

    /**
     * Test expires_at must be after published_at.
     */
    public function test_expires_at_must_be_after_published_at(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'published_at' => '2024-12-31',
            'expires_at' => '2024-01-01', // Before published_at
        ]);

        $response->assertSessionHasErrors('expires_at');
    }

    /**
     * Test checkboxes handle unchecked state properly.
     */
    public function test_checkboxes_handle_unchecked_state(): void
    {
        $project = Project::factory()->create();

        // Submit without checkboxes (unchecked)
        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            // No is_dofollow or invoice_paid (unchecked)
        ]);

        $response->assertRedirect(route('backlinks.index'));

        // is_dofollow n'est pas settable à la création (géré par le checker)
        // Seule invoice_paid est gérée par le formulaire
        $this->assertDatabaseHas('backlinks', [
            'project_id' => $project->id,
            'invoice_paid' => false,
        ]);
        // last_checked_at null → is_dofollow null (jamais vérifié)
        $backlink = \App\Models\Backlink::where('project_id', $project->id)->first();
        $this->assertNull($backlink->is_dofollow);
    }

    /**
     * Test cannot create self-referencing backlink on update.
     */
    public function test_cannot_update_to_self_reference(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $response = $this->put(route('backlinks.update', $backlink), [
            'project_id' => $project->id,
            'source_url' => $backlink->source_url,
            'target_url' => $backlink->target_url,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $backlink->id, // Self-reference
            'spot_type' => 'external',
        ]);

        $response->assertSessionHasErrors('parent_backlink_id');
    }

    /**
     * Test cannot update to create circular reference (A points to B, B points to A).
     */
    public function test_cannot_update_to_circular_reference(): void
    {
        $project = Project::factory()->create();

        // Create A (tier1)
        $backlinkA = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        // Create B (tier2) with A as parent
        $backlinkB = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $backlinkA->id,
        ]);

        // Try to update A to point to B as parent (circular: A->B, B->A)
        $response = $this->put(route('backlinks.update', $backlinkA), [
            'project_id' => $project->id,
            'source_url' => $backlinkA->source_url,
            'target_url' => $backlinkA->target_url,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $backlinkB->id, // Circular reference
            'spot_type' => 'external',
        ]);

        $response->assertSessionHasErrors('parent_backlink_id');
    }

    /**
     * Test cannot convert tier1 with children to tier2.
     */
    public function test_cannot_convert_tier1_with_children_to_tier2(): void
    {
        $project = Project::factory()->create();

        // Create parent (tier1)
        $parent = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        // Create child (tier2)
        Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parent->id,
        ]);

        // Try to convert parent to tier2 (should fail because it has children)
        $anotherTier1 = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $response = $this->put(route('backlinks.update', $parent), [
            'project_id' => $project->id,
            'source_url' => $parent->source_url,
            'target_url' => $parent->target_url,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $anotherTier1->id,
            'spot_type' => 'external',
        ]);

        $response->assertSessionHasErrors('parent_backlink_id');
    }

    /**
     * Test authenticated user is tracked as creator.
     */
    public function test_authenticated_user_tracked_as_creator(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
        ]);

        $response->assertRedirect(route('backlinks.index'));
        $this->assertDatabaseHas('backlinks', [
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Test all supported currencies are valid.
     */
    public function test_all_supported_currencies_are_valid(): void
    {
        $project = Project::factory()->create();
        $currencies = ['EUR', 'USD', 'GBP', 'CAD', 'BRL', 'MXN', 'ARS', 'COP', 'CLP', 'PEN'];

        foreach ($currencies as $currency) {
            $response = $this->post(route('backlinks.store'), [
                'project_id' => $project->id,
                'source_url' => "https://example-{$currency}.com/page",
                'target_url' => 'https://target.com',
                'tier_level' => 'tier1',
                'spot_type' => 'external',
                'price' => 100.00,
                'currency' => $currency,
            ]);

            $response->assertRedirect(route('backlinks.index'));
        }

        $this->assertCount(10, Backlink::all());
    }

    /**
     * Test invalid currency is rejected.
     */
    public function test_invalid_currency_is_rejected(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'price' => 100.00,
            'currency' => 'XYZ', // Invalid currency
        ]);

        $response->assertSessionHasErrors('currency');
    }
}
