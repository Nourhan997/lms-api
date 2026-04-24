<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4, false);

        return [
            'instructor_id'    => User::factory()->instructor(),
            'category_id'      => Category::factory(),
            'next_course_id'   => null,
            'title'            => $title,
            'title_ar'         => null,
            'slug'             => Str::slug($title),
            'description'      => fake()->paragraphs(3, true),
            'description_ar'   => null,
            'thumbnail'        => null,
            'level'            => fake()->randomElement(CourseLevel::cases()),
            'language'         => CourseLanguage::En,
            'price'            => 0,
            'currency'         => 'OMR',
            'status'           => CourseStatus::Draft,
            'duration_minutes' => fake()->numberBetween(60, 600),
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => CourseStatus::Published,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn(array $attributes) => [
            'price' => 0,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn(array $attributes) => [
            'price' => fake()->randomFloat(2, 10, 200),
        ]);
    }
}
