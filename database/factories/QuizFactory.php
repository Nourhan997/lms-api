<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id'   => Section::factory(),
            'title'        => fake()->sentence(3, false),
            'pass_score'   => 70,
            'is_published' => true,
        ];
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn(array $attributes) => [
            'section_id' => $section->id,
        ]);
    }
}
