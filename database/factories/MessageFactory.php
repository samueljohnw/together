<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }

    public function withAttachment(string $path = 'attachments/example.pdf', string $name = 'example.pdf'): static
    {
        return $this->state(fn (array $attributes) => [
            'body' => null,
            'attachment_path' => $path,
            'attachment_name' => $name,
            'attachment_mime' => 'application/pdf',
            'attachment_size' => 1024,
        ]);
    }
}
