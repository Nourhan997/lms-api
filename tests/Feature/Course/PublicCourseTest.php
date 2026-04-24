<?php

declare(strict_types=1);

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_published_courses(): void
    {
        Course::factory()->published()->count(3)->create();
        Course::factory()->count(2)->create(); // drafts

        $response = $this->getJson('/api/v1/public/courses');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug', 'level', 'language', 'price', 'is_free', 'status']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_guest_can_filter_courses_by_category(): void
    {
        $english = Category::factory()->create(['slug' => 'english-test']);
        $french  = Category::factory()->create(['slug' => 'french-test']);

        Course::factory()->published()->create(['category_id' => $english->id]);
        Course::factory()->published()->create(['category_id' => $french->id]);

        $this->getJson('/api/v1/public/courses?category=english-test')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_can_filter_courses_by_level(): void
    {
        Course::factory()->published()->create(['level' => 'beginner']);
        Course::factory()->published()->create(['level' => 'advanced']);

        $this->getJson('/api/v1/public/courses?level=beginner')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.level', 'beginner');
    }

    public function test_guest_can_search_courses(): void
    {
        Course::factory()->published()->create(['title' => 'Advanced English Grammar']);
        Course::factory()->published()->create(['title' => 'French for Beginners']);

        $this->getJson('/api/v1/public/courses?search=English')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Advanced English Grammar');
    }

    public function test_guest_can_view_course_detail_by_slug(): void
    {
        $course = Course::factory()->published()->create(['slug' => 'my-test-course']);

        $this->getJson('/api/v1/public/courses/my-test-course')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'my-test-course')
            ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'description', 'sections']]);
    }

    public function test_draft_courses_not_visible_to_public(): void
    {
        Course::factory()->published()->count(2)->create();
        $draft = Course::factory()->create(['slug' => 'secret-draft', 'status' => 'draft']);

        $this->getJson('/api/v1/public/courses')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/public/courses/secret-draft')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
