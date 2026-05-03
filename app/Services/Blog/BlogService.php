<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Exceptions\BlogPostNotFoundException;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlogService
{
    public function getPublished(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $page = request()->get('page', 1);

        if (!empty($filters['search'])) {
            return BlogPost::published()
                ->with('author:id,name')
                ->where(function ($q) use ($filters): void {
                    $q->where('title', 'like', "%{$filters['search']}%")
                      ->orWhere('body', 'like', "%{$filters['search']}%");
                })
                ->orderByDesc('published_at')
                ->paginate($perPage);
        }

        return Cache::tags(['blog'])->remember(
            "blog.published.page.{$page}",
            600,
            fn() => BlogPost::published()
                ->with('author:id,name')
                ->orderByDesc('published_at')
                ->paginate($perPage)
        );
    }

    public function getBySlug(string $slug): BlogPost
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with('author:id,name')
            ->first();

        if (!$post) {
            throw new BlogPostNotFoundException();
        }

        return $post;
    }

    public function getAllForAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return BlogPost::with('author:id,name')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, User $author): BlogPost
    {
        $data['author_id']    = $author->id;
        $data['slug']         = Str::slug($data['title']);
        $data['published_at'] = !empty($data['is_published'] ?? false) ? now() : null;

        $post = BlogPost::create($data);
        $this->clearCache();

        return $post->load('author:id,name');
    }

    public function update(BlogPost $post, array $data): BlogPost
    {
        if (!empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if (!empty($data['is_published']) && $post->published_at === null) {
            $data['published_at'] = now();
        }

        $post->update($data);
        $this->clearCache();

        return $post->fresh()->load('author:id,name');
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::tags(['blog'])->flush();
    }
}
