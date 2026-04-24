<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        static $order = 1;

        return [
            'section_id'       => Section::factory(),
            'title'            => fake()->sentence(3, false),
            'title_ar'         => null,
            'order'            => $order++,
            'is_published'     => false,
            'duration_minutes' => fake()->numberBetween(5, 60),
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn(array $attributes) => [
            'section_id' => $section->id,
        ]);
    }
}
