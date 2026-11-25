<?php

namespace Database\Factories;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => ItemKind::Todo,
            'title' => fake()->sentence(4),
            'user_id' => User::factory(),
            'last_activity_at' => now(),
        ];
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ItemKind::Todo,
        ]);
    }

    public function thread(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ItemKind::Thread,
        ]);
    }

    public function closed(?User $closedBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
            'closed_by_id' => $closedBy?->getKey() ?? $attributes['user_id'],
        ]);
    }
}
