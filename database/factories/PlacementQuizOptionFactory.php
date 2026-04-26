<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlacementQuizOption;
use App\Models\PlacementQuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementQuizOption>
 */
class PlacementQuizOptionFactory extends Factory
{
    private static int $order = 0;

    public function definition(): array
    {
        return [
            'placement_quiz_question_id' => PlacementQuizQuestion::factory(),
            'option_text'                => fake()->word(),
            'is_correct'                 => false,
            'order'                      => ++self::$order,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }

    public function forQuestion(PlacementQuizQuestion $question): static
    {
        return $this->state(['placement_quiz_question_id' => $question->id]);
    }
}
