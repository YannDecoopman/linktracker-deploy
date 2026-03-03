<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Backlink>
 */
class BacklinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_url' => fake()->url(),
            'target_url' => fake()->url(),
            'anchor_text' => fake()->words(3, true),
            'status' => 'active',
            'http_status' => 200,
            'rel_attributes' => 'follow',
            'is_dofollow' => true,
            'first_seen_at' => now(),
            'last_checked_at' => now(),
        ];
    }

    /**
     * Indicate that the backlink is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'http_status' => 200,
            'is_dofollow' => true,
            'rel_attributes' => 'follow',
        ]);
    }

    /**
     * Indicate that the backlink is lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'http_status' => 404,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Indicate that the backlink has changed.
     */
    public function changed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'changed',
            'http_status' => 200,
            'is_dofollow' => false,
            'rel_attributes' => 'nofollow',
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Indicate that the backlink is nofollow.
     */
    public function nofollow(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dofollow' => false,
            'rel_attributes' => 'nofollow',
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Indicate that the backlink has never been checked.
     */
    public function unchecked(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_checked_at' => null,
        ]);
    }
}
