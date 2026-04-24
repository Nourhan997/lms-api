<?php

declare(strict_types=1);

namespace Tests\Feature\Section;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function instructorToken(User $instructor): array
    {
        $token = $instructor->createToken('auth-token')->plainTextToken;

        return [$instructor, $token];
    }

    private function adminToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_instructor_can_create_section(): void
    {
        $instructor         = User::factory()->instructor()->create();
        [, $token]          = $this->instructorToken($instructor);
        $course             = Course::factory()->create(['instructor_id' => $instructor->id]);

        $response = $this->withToken($token)->postJson(
            "/api/v1/instructor/courses/{$course->id}/sections",
            ['title' => 'Introduction to PHP']
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Introduction to PHP')
            ->assertJsonPath('data.course_id', $course->id);

        $this->assertDatabaseHas('sections', [
            'course_id' => $course->id,
            'title'     => 'Introduction to PHP',
        ]);
    }

    public function test_instructor_can_reorder_sections(): void
    {
        $instructor = User::factory()->instructor()->create();
        [, $token]  = $this->instructorToken($instructor);
        $course     = Course::factory()->create(['instructor_id' => $instructor->id]);

        $s1 = Section::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $s2 = Section::factory()->create(['course_id' => $course->id, 'order' => 2]);
        $s3 = Section::factory()->create(['course_id' => $course->id, 'order' => 3]);

        $this->withToken($token)->postJson(
            "/api/v1/instructor/courses/{$course->id}/sections/reorder",
            ['section_ids' => [$s3->id, $s1->id, $s2->id]]
        )->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('sections', ['id' => $s3->id, 'order' => 1]);
        $this->assertDatabaseHas('sections', ['id' => $s1->id, 'order' => 2]);
        $this->assertDatabaseHas('sections', ['id' => $s2->id, 'order' => 3]);
    }

    public function test_instructor_cannot_manage_other_instructors_course(): void
    {
        $owner      = User::factory()->instructor()->create();
        $other      = User::factory()->instructor()->create();
        $course     = Course::factory()->create(['instructor_id' => $owner->id]);
        [, $token]  = $this->instructorToken($other);

        $this->withToken($token)
            ->postJson("/api/v1/instructor/courses/{$course->id}/sections", ['title' => 'Hack'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->withToken($token)
            ->getJson("/api/v1/instructor/courses/{$course->id}/sections")
            ->assertStatus(403);
    }

    public function test_admin_can_manage_any_course_sections(): void
    {
        [, $adminToken] = $this->adminToken();
        $instructor     = User::factory()->instructor()->create();
        $course         = Course::factory()->create(['instructor_id' => $instructor->id]);

        $response = $this->withToken($adminToken)->postJson(
            "/api/v1/admin/courses/{$course->id}/sections",
            ['title' => 'Admin Created Section']
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Admin Created Section');

        $this->assertDatabaseHas('sections', [
            'course_id' => $course->id,
            'title'     => 'Admin Created Section',
        ]);
    }

    public function test_instructor_cannot_delete_section_with_published_lessons(): void
    {
        $instructor = User::factory()->instructor()->create();
        [, $token]  = $this->instructorToken($instructor);
        $course     = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section    = Section::factory()->create(['course_id' => $course->id]);

        Lesson::factory()->published()->create(['section_id' => $section->id]);

        $this->withToken($token)
            ->deleteJson("/api/v1/instructor/courses/{$course->id}/sections/{$section->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    public function test_instructor_can_delete_section_without_published_lessons(): void
    {
        $instructor = User::factory()->instructor()->create();
        [, $token]  = $this->instructorToken($instructor);
        $course     = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section    = Section::factory()->create(['course_id' => $course->id]);

        $this->withToken($token)
            ->deleteJson("/api/v1/instructor/courses/{$course->id}/sections/{$section->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    public function test_section_auto_assigns_order_on_create(): void
    {
        $instructor = User::factory()->instructor()->create();
        [, $token]  = $this->instructorToken($instructor);
        $course     = Course::factory()->create(['instructor_id' => $instructor->id]);

        Section::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Section::factory()->create(['course_id' => $course->id, 'order' => 2]);

        $response = $this->withToken($token)->postJson(
            "/api/v1/instructor/courses/{$course->id}/sections",
            ['title' => 'Third Section']
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('sections', ['title' => 'Third Section', 'order' => 3]);
    }
}
