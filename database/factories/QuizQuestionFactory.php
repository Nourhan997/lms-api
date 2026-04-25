<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuizQuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        static $order = 1;

        return [
            'quiz_id'     => Quiz::factory(),
            'question'    => fake()->sentence() . '?',
            'type'        => QuizQuestionType::MultipleChoice,
            'explanation' => null,
            'order'       => $order++,
        ];
    }

    public function forQuiz(Quiz $quiz): static
    {
        return $this->state(fn(array $attributes) => [
            'quiz_id' => $quiz->id,
        ]);
    }

    public function fillBlank(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => QuizQuestionType::FillBlank,
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => QuizQuestionType::TrueFalse,
        ]);
    }
}
