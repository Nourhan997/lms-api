<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Http\Resources\Admin\AdminBlogPostResource;
use App\Models\BlogPost;
use App\Services\Blog\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AdminBlogController extends Controller
{
    public function __construct(
        private readonly BlogService $blogService,
    ) {}

    public function index(): JsonResponse
    {
        $posts = $this->blogService->getAllForAdmin();

        return response()->json([
            'success' => true,
            'data'    => AdminBlogPostResource::collection($posts),
            'message' => 'Blog posts retrieved successfully.',
            'meta'    => [
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        $post = $this->blogService->create($request->validated(), auth()->user());

        return response()->json([
            'success' => true,
            'data'    => new AdminBlogPostResource($post),
            'message' => 'Blog post created successfully.',
            'meta'    => [],
        ], 201);
    }

    public function show(BlogPost $post): JsonResponse
    {
        $post->load('author:id,name');

        return response()->json([
            'success' => true,
            'data'    => new AdminBlogPostResource($post),
            'message' => 'Blog post retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): JsonResponse
    {
        $post = $this->blogService->update($post, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminBlogPostResource($post),
            'message' => 'Blog post updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(BlogPost $post): Response
    {
        $this->blogService->delete($post);

        return response()->noContent();
    }

    public function publish(BlogPost $post): JsonResponse
    {
        $post = $this->blogService->update($post, ['is_published' => true]);

        return response()->json([
            'success' => true,
            'data'    => new AdminBlogPostResource($post),
            'message' => 'Blog post published successfully.',
            'meta'    => [],
        ]);
    }

    public function unpublish(BlogPost $post): JsonResponse
    {
        $post = $this->blogService->update($post, ['is_published' => false]);

        return response()->json([
            'success' => true,
            'data'    => new AdminBlogPostResource($post),
            'message' => 'Blog post unpublished successfully.',
            'meta'    => [],
        ]);
    }
}
