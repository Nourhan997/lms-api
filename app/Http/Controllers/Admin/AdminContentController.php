<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContentRequest;
use App\Http\Requests\Admin\UpdateContentRequest;
use App\Http\Resources\LessonContentResource;
use App\Models\Lesson;
use App\Models\LessonContent;
use App\Services\Course\ContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminContentController extends Controller
{
    public function __construct(
        private readonly ContentService $contentService
    ) {}

    public function index(Lesson $lesson): JsonResponse
    {
        $contents = $this->contentService->getForLesson($lesson);

        return response()->json([
            'success' => true,
            'data'    => LessonContentResource::collection($contents),
            'message' => 'Content retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function store(StoreContentRequest $request, Lesson $lesson): JsonResponse
    {
        $data            = $request->validated();
        $data['file_path'] = $this->contentService->handleFileUpload($request, 'file');

        $content = $this->contentService->addContent($lesson, $data);

        return response()->json([
            'success' => true,
            'data'    => new LessonContentResource($content),
            'message' => 'Content added successfully.',
            'meta'    => [],
        ], 201);
    }

    public function update(UpdateContentRequest $request, Lesson $lesson, LessonContent $content): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->contentService->handleFileUpload($request, 'file');
        }

        $content = $this->contentService->update($content, $data);

        return response()->json([
            'success' => true,
            'data'    => new LessonContentResource($content),
            'message' => 'Content updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(Lesson $lesson, LessonContent $content): Response
    {
        $this->contentService->delete($content);

        return response()->noContent();
    }

    public function reorder(Request $request, Lesson $lesson): JsonResponse
    {
        $request->validate([
            'content_ids'   => ['required', 'array'],
            'content_ids.*' => ['integer', 'exists:lesson_contents,id'],
        ]);

        $this->contentService->reorder($lesson, $request->input('content_ids'));

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Content reordered successfully.',
            'meta'    => [],
        ]);
    }
}
