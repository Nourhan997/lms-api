<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'type'    => 'general',
            'title'   => $this->faker->sentence(4),
            'body'    => $this->faker->paragraph(),
            'data'    => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
