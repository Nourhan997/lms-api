<?php

declare(strict_types=1);

namespace Tests\Feature\Course;

use App\Enums\EnrollmentStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_admin_can_create_course(): void
    {
        [, $token]   = $this->adminToken();
        $instructor  = User::factory()->instructor()->create();
        $category    = Category::factory()->create();

        $response = $this->withToken($token)->postJson('/api/v1/admin/courses', [
            'title'         => 'New English Course',
            'description'   => 'A comprehensive English course for all levels.',
            'instructor_id' => $instructor->id,
            'category_id'   => $category->id,
            'level'         => 'beginner',
            'language'      => 'en',
            'price'         => 0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'New English Course')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('courses', ['title' => 'New English Course']);
    }

    public function test_admin_can_update_course(): void
    {
        [, $token]  = $this->adminToken();
        $course     = Course::factory()->create();
        $category   = Category::factory()->create();

        $this->withToken($token)->putJson("/api/v1/admin/courses/{$course->id}", [
            'title'       => 'Updated Title',
            'description' => 'Updated description for this course.',
            'category_id' => $category->id,
            'level'       => 'intermediate',
            'language'    => 'en',
            'price'       => 29.99,
        ])->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.price', '29.99');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Updated Title']);
    }

    public function test_admin_can_publish_course(): void
    {
        [, $token] = $this->adminToken();
        $course    = Course::factory()->create(['status' => 'draft']);

        $this->withToken($token)
            ->postJson("/api/v1/admin/courses/{$course->id}/publish")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => 'published']);
    }

    public function test_admin_can_archive_course(): void
    {
        [, $token] = $this->adminToken();
        $course    = Course::factory()->published()->create();

        $this->withToken($token)
            ->postJson("/api/v1/admin/courses/{$course->id}/archive")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'archived');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => 'archived']);
    }

    public function test_admin_cannot_delete_course_with_active_enrollments(): void
    {
        [, $token] = $this->adminToken();
        $course    = Course::factory()->create();
        $student   = User::factory()->student()->create();

        Enrollment::create([
            'user_id'     => $student->id,
            'course_id'   => $course->id,
            'status'      => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson("/api/v1/admin/courses/{$course->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/admin/courses')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->withToken($token)
            ->postJson('/api/v1/admin/courses', [])
            ->assertStatus(403);
    }
}
