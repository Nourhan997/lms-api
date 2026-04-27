<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'           => User::factory()->student(),
            'course_id'         => Course::factory()->premium()->published(),
            'amount'            => fake()->randomFloat(2, 10, 200),
            'currency'          => 'OMR',
            'status'            => PaymentStatus::Pending,
            'gateway'           => 'demo',
            'gateway_reference' => null,
            'paid_at'           => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'  => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn(array $attributes) => [
            'course_id' => $course->id,
            'amount'    => $course->price,
            'currency'  => $course->currency,
        ]);
    }
}
