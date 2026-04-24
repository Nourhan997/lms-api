<?php

declare(strict_types=1);

namespace Tests\Feature\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonManagementTest extends TestCase
{
    use RefreshDatabase;

    private function instructorWithCourse(): array
    {
        $instructor = User::factory()->instructor()->create();
        $course     = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section    = Section::factory()->create(['course_id' => $course->id]);
        $token      = $instructor->createToken('auth-token')->plainTextToken;

        return [$instructor, $course, $section, $token];
    }

    public function test_instructor_can_create_lesson(): void
    {
        [, , $section, $token] = $this->instructorWithCourse();

        $response = $this->withToken($token)->postJson(
            "/api/v1/instructor/sections/{$section->id}/lessons",
            [
                'title'            => 'Variables and Types',
                'duration_minutes' => 15,
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Variables and Types')
            ->assertJsonPath('data.section_id', $section->id)
            ->assertJsonPath('data.duration_minutes', 15);

        $this->assertDatabaseHas('lessons', [
            'section_id' => $section->id,
            'title'      => 'Variables and Types',
        ]);
    }

    public function test_instructor_can_add_video_content(): void
    {
        [, , $section, $token] = $this->instructorWithCourse();
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        $response = $this->withToken($token)->postJson(
            "/api/v1/instructor/lessons/{$lesson->id}/contents",
            [
                'type'    => 'video',
                'content' => 'https://www.youtube.com/watch?v=example',
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'video')
            ->assertJsonPath('data.content', 'https://www.youtube.com/watch?v=example');

        $this->assertDatabaseHas('lesson_contents', [
            'lesson_id' => $lesson->id,
            'type'      => 'video',
        ]);
    }

    public function test_instructor_can_upload_pdf_content(): void
    {
        Storage::fake('public');

        [, , $section, $token] = $this->instructorWithCourse();
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        $file = UploadedFile::fake()->create('lecture.pdf', 500, 'application/pdf');

        $response = $this->withToken($token)->postJson(
            "/api/v1/instructor/lessons/{$lesson->id}/contents",
            [
                'type' => 'pdf',
                'file' => $file,
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'pdf');

        $content = \App\Models\LessonContent::where('lesson_id', $lesson->id)->first();
        $this->assertNotNull($content->file_path);
        Storage::disk('public')->assertExists($content->file_path);
    }

    public function test_content_reorder_updates_order_column(): void
    {
        [, , $section, $token] = $this->instructorWithCourse();
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        $c1 = \App\Models\LessonContent::create(['lesson_id' => $lesson->id, 'type' => 'text', 'content' => 'A', 'order' => 1]);
        $c2 = \App\Models\LessonContent::create(['lesson_id' => $lesson->id, 'type' => 'text', 'content' => 'B', 'order' => 2]);
        $c3 = \App\Models\LessonContent::create(['lesson_id' => $lesson->id, 'type' => 'text', 'content' => 'C', 'order' => 3]);

        $this->withToken($token)->postJson(
            "/api/v1/instructor/lessons/{$lesson->id}/contents/reorder",
            ['content_ids' => [$c3->id, $c1->id, $c2->id]]
        )->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('lesson_contents', ['id' => $c3->id, 'order' => 1]);
        $this->assertDatabaseHas('lesson_contents', ['id' => $c1->id, 'order' => 2]);
        $this->assertDatabaseHas('lesson_contents', ['id' => $c2->id, 'order' => 3]);
    }

    public function test_instructor_can_reorder_lessons(): void
    {
        [, , $section, $token] = $this->instructorWithCourse();

        $l1 = Lesson::factory()->create(['section_id' => $section->id, 'order' => 1]);
        $l2 = Lesson::factory()->create(['section_id' => $section->id, 'order' => 2]);
        $l3 = Lesson::factory()->create(['section_id' => $section->id, 'order' => 3]);

        $this->withToken($token)->postJson(
            "/api/v1/instructor/sections/{$section->id}/lessons/reorder",
            ['lesson_ids' => [$l2->id, $l3->id, $l1->id]]
        )->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('lessons', ['id' => $l2->id, 'order' => 1]);
        $this->assertDatabaseHas('lessons', ['id' => $l3->id, 'order' => 2]);
        $this->assertDatabaseHas('lessons', ['id' => $l1->id, 'order' => 3]);
    }

    public function test_instructor_cannot_manage_lesson_in_another_instructors_section(): void
    {
        $owner   = User::factory()->instructor()->create();
        $other   = User::factory()->instructor()->create();
        $course  = Course::factory()->create(['instructor_id' => $owner->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $token   = $other->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/instructor/sections/{$section->id}/lessons", ['title' => 'Hack'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_lesson_content_text_requires_content_field(): void
    {
        [, , $section, $token] = $this->instructorWithCourse();
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        $this->withToken($token)->postJson(
            "/api/v1/instructor/lessons/{$lesson->id}/contents",
            ['type' => 'text']
        )->assertStatus(422);
    }
}
