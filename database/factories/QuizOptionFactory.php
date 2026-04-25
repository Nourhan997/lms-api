<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizOption>
 */
class QuizOptionFactory extends Factory
{
    public function definition(): array
    {
        static $order = 1;

        return [
            'quiz_question_id' => QuizQuestion::factory(),
            'option_text'      => fake()->sentence(3, false),
            'is_correct'       => false,
            'order'            => $order++,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_correct' => true,
        ]);
    }

    public function forQuestion(QuizQuestion $question): static
    {
        return $this->state(fn(array $attributes) => [
            'quiz_question_id' => $question->id,
        ]);
    }
}
