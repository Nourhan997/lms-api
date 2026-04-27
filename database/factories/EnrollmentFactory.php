<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory()->student(),
            'course_id'   => Course::factory()->published(),
            'payment_id'  => null,
            'status'      => EnrollmentStatus::Active,
            'enrolled_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => EnrollmentStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
