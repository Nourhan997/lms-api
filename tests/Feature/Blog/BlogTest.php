<?php

declare(strict_types=1);

namespace Tests\Feature\Blog;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_guest_can_browse_published_posts(): void
    {
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->draft()->count(2)->create();

        $this->getJson('/api/v1/public/blog')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_cannot_see_draft_posts(): void
    {
        BlogPost::factory()->draft()->count(2)->create();

        $this->getJson('/api/v1/public/blog')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_guest_can_view_post_by_slug(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->getJson("/api/v1/public/blog/{$post->slug}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $post->slug)
            ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'body', 'author']]);
    }

    public function test_admin_can_create_blog_post(): void
    {
        [$admin, $adminToken] = $this->adminWithToken();

        $this->withToken($adminToken)
            ->postJson('/api/v1/admin/blog', [
                'title'        => 'My First Blog Post',
                'body'         => '<p>This is the body content of the blog post.</p>',
                'is_published' => false,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_published', false);

        $this->assertDatabaseHas('blog_posts', ['slug' => 'my-first-blog-post']);
    }

    public function test_admin_can_publish_draft_post(): void
    {
        [$admin, $adminToken] = $this->adminWithToken();

        $post = BlogPost::factory()->draft()->create(['author_id' => $admin->id]);

        $this->withToken($adminToken)
            ->postJson("/api/v1/admin/blog/{$post->id}/publish")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_published', true);

        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'is_published' => true]);
    }

    public function test_admin_can_delete_post(): void
    {
        [$admin, $adminToken] = $this->adminWithToken();

        $post = BlogPost::factory()->create(['author_id' => $admin->id]);

        $this->withToken($adminToken)
            ->deleteJson("/api/v1/admin/blog/{$post->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }
}
