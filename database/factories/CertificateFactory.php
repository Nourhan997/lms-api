<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        $user   = User::factory()->student()->create();
        $course = Course::factory()->published()->create();

        $enrollment = Enrollment::factory()->completed()->create([
            'user_id'  => $user->id,
            'course_id' => $course->id,
        ]);

        return [
            'user_id'         => $user->id,
            'course_id'       => $course->id,
            'enrollment_id'   => $enrollment->id,
            'certificate_uid' => strtoupper(fake()->uuid()),
            'issued_at'       => now(),
            'pdf_path'        => null,
        ];
    }

    public function forEnrollment(Enrollment $enrollment): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id'   => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'enrollment_id' => $enrollment->id,
        ]);
    }
}
