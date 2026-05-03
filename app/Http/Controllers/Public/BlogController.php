<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\BlogPostDetailResource;
use App\Http\Resources\Public\BlogPostListResource;
use App\Services\Blog\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogService $blogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search']);
        $posts   = $this->blogService->getPublished($filters);

        return response()->json([
            'success' => true,
            'data'    => BlogPostListResource::collection($posts),
            'message' => 'Blog posts retrieved successfully.',
            'meta'    => [
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = $this->blogService->getBySlug($slug);

        return response()->json([
            'success' => true,
            'data'    => new BlogPostDetailResource($post),
            'message' => 'Blog post retrieved successfully.',
            'meta'    => [],
        ]);
    }
}
