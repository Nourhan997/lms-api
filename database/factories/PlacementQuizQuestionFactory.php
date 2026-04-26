<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlacementQuiz;
use App\Models\PlacementQuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementQuizQuestion>
 */
class PlacementQuizQuestionFactory extends Factory
{
    private static int $order = 0;

    public function definition(): array
    {
        return [
            'placement_quiz_id' => PlacementQuiz::factory(),
            'question'          => fake()->sentence() . '?',
            'type'              => 'multiple_choice',
            'order'             => ++self::$order,
        ];
    }

    public function forQuiz(PlacementQuiz $quiz): static
    {
        return $this->state(['placement_quiz_id' => $quiz->id]);
    }

    public function trueFalse(): static
    {
        return $this->state(['type' => 'true_false']);
    }
}
