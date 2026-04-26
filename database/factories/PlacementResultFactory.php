<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlacementQuiz;
use App\Models\PlacementResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementResult>
 */
class PlacementResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'placement_quiz_id' => PlacementQuiz::factory(),
            'score_min'         => 0,
            'score_max'         => 100,
            'label'             => fake()->words(3, true),
            'course_id'         => null,
        ];
    }

    public function forQuiz(PlacementQuiz $quiz): static
    {
        return $this->state(['placement_quiz_id' => $quiz->id]);
    }
}
