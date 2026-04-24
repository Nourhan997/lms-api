<?php

declare(strict_types=1);

namespace Tests\Feature\Enrollment;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledStudent(Course $course): array
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $enrollment = Enrollment::create([
            'user_id'     => $student->id,
            'course_id'   => $course->id,
            'status'      => 'active',
            'enrolled_at' => now(),
        ]);

        return [$student, $token, $enrollment];
    }

    public function test_student_can_enroll_in_free_published_course(): void
    {
        Event::fake();

        $student = User::factory()->student()->create();
        $course  = Course::factory()->published()->free()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/enroll");

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('enrollments', [
            'user_id'   => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_student_cannot_enroll_in_premium_course_without_payment(): void
    {
        $student = User::factory()->student()->create();
        $course  = Course::factory()->published()->premium()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/enroll")
            ->assertStatus(402)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_enroll_twice(): void
    {
        Event::fake();

        $student = User::factory()->student()->create();
        $course  = Course::factory()->published()->free()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)->postJson("/api/v1/student/courses/{$course->id}/enroll");

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/enroll")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_enroll_in_unpublished_course(): void
    {
        $student = User::factory()->student()->create();
        $course  = Course::factory()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/enroll")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_list_enrollments(): void
    {
        $course = Course::factory()->published()->free()->create();
        [, $token] = $this->enrolledStudent($course);

        $this->withToken($token)
            ->getJson('/api/v1/student/enrollments')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_student_can_view_enrollment_detail(): void
    {
        $course = Course::factory()->published()->free()->create();
        [, $token, $enrollment] = $this->enrolledStudent($course);

        $this->withToken($token)
            ->getJson("/api/v1/student/enrollments/{$enrollment->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $enrollment->id)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_student_can_complete_a_lesson(): void
    {
        Event::fake();

        $course  = Course::factory()->published()->free()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson  = Lesson::factory()->create(['section_id' => $section->id]);
        [$student, $token] = $this->enrolledStudent($course);

        $this->withToken($token)
            ->postJson("/api/v1/student/lessons/{$lesson->id}/complete")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_completed', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id'      => $student->id,
            'lesson_id'    => $lesson->id,
            'is_completed' => 1,
        ]);
    }

    public function test_completing_all_lessons_marks_enrollment_complete(): void
    {
        Event::fake();

        $course  = Course::factory()->published()->free()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson  = Lesson::factory()->create(['section_id' => $section->id]);
        [, $token, $enrollment] = $this->enrolledStudent($course);

        $this->withToken($token)
            ->postJson("/api/v1/student/lessons/{$lesson->id}/complete")
            ->assertStatus(200);

        $this->assertDatabaseHas('enrollments', [
            'id'     => $enrollment->id,
            'status' => 'completed',
        ]);
    }

    public function test_student_can_view_course_progress(): void
    {
        $course  = Course::factory()->published()->free()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $l1      = Lesson::factory()->create(['section_id' => $section->id]);
        $l2      = Lesson::factory()->create(['section_id' => $section->id]);
        [$student, $token, $enrollment] = $this->enrolledStudent($course);

        LessonProgress::create([
            'user_id'       => $student->id,
            'lesson_id'     => $l1->id,
            'enrollment_id' => $enrollment->id,
            'is_completed'  => true,
            'completed_at'  => now(),
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/student/enrollments/{$enrollment->id}/progress")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_lessons', 2)
            ->assertJsonPath('data.completed_lessons', 1)
            ->assertJsonPath('data.percentage', 50);
    }

    public function test_student_cannot_access_another_students_enrollment(): void
    {
        $course = Course::factory()->published()->free()->create();
        [, , $enrollment] = $this->enrolledStudent($course);

        $other = User::factory()->student()->create();
        $token = $other->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/student/enrollments/{$enrollment->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_unenrolled_student_cannot_complete_lesson(): void
    {
        $course  = Course::factory()->published()->free()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson  = Lesson::factory()->create(['section_id' => $section->id]);

        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/student/lessons/{$lesson->id}/complete")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
