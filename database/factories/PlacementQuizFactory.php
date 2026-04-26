<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlacementQuiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementQuiz>
 */
class PlacementQuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(3, false),
            'subject'     => 'english',
            'description' => fake()->sentence(),
            'is_active'   => true,
        ];
    }

    public function french(): static
    {
        return $this->state(['subject' => 'french']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
