<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreSectionRequest;
use App\Http\Requests\Instructor\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Course;
use App\Models\Section;
use App\Services\Course\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstructorSectionController extends Controller
{
    public function __construct(
        private readonly SectionService $sectionService
    ) {}

    public function index(Course $course): JsonResponse
    {
        $sections = $this->sectionService->getForCourse($course);

        return response()->json([
            'success' => true,
            'data'    => SectionResource::collection($sections),
            'message' => 'Sections retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function store(StoreSectionRequest $request, Course $course): JsonResponse
    {
        $section = $this->sectionService->create($course, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new SectionResource($section),
            'message' => 'Section created successfully.',
            'meta'    => [],
        ], 201);
    }

    public function update(UpdateSectionRequest $request, Course $course, Section $section): JsonResponse
    {
        $section = $this->sectionService->update($section, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new SectionResource($section),
            'message' => 'Section updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(Course $course, Section $section): Response
    {
        $this->sectionService->delete($section);

        return response()->noContent();
    }

    public function reorder(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'section_ids'   => ['required', 'array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
        ]);

        $this->sectionService->reorder($course, $request->input('section_ids'));

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Sections reordered successfully.',
            'meta'    => [],
        ]);
    }
}
