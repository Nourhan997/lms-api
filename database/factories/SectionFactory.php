<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        static $order = 1;

        return [
            'course_id'    => Course::factory(),
            'title'        => fake()->sentence(3, false),
            'title_ar'     => null,
            'order'        => $order++,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn(array $attributes) => [
            'course_id' => $course->id,
        ]);
    }
}
