<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Exceptions\PlacementAlreadyCompletedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitPlacementRequest;
use App\Http\Resources\PlacementQuizResource;
use App\Http\Resources\Student\CourseListResource;
use App\Services\Quiz\PlacementQuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    public function __construct(
        private readonly PlacementQuizService $placementQuizService,
    ) {}

    public function show(Request $request, string $subject): JsonResponse
    {
        if (!in_array($subject, ['english', 'french'], true)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Invalid subject. Must be english or french.',
                'meta'    => [],
            ], 404);
        }

        $quiz = $this->placementQuizService->getActive($subject);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'No active placement test found for this subject.',
                'meta'    => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new PlacementQuizResource($quiz),
            'message' => 'Placement quiz retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function submit(SubmitPlacementRequest $request, string $subject): JsonResponse
    {
        if (!in_array($subject, ['english', 'french'], true)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Invalid subject. Must be english or french.',
                'meta'    => [],
            ], 404);
        }

        if ($request->user()->placement_completed_at !== null) {
            throw new PlacementAlreadyCompletedException();
        }

        $quiz = $this->placementQuizService->getActive($subject);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'No active placement test found for this subject.',
                'meta'    => [],
            ], 404);
        }

        $data = $this->placementQuizService->submitPlacementAttempt(
            $request->user(),
            $quiz,
            $request->validated()['answers'],
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'score'            => $data['score'],
                'percentage'       => $data['percentage'],
                'label'            => $data['label'],
                'suggested_course' => $data['course'] ? new CourseListResource($data['course']) : null,
            ],
            'message' => 'Placement test submitted successfully.',
            'meta'    => [],
        ]);
    }

    public function result(Request $request): JsonResponse
    {
        $data = $this->placementQuizService->getResult($request->user());

        if (!$data) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Placement test not yet completed.',
                'meta'    => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'score'        => $data['score'],
                'label'        => $data['label'],
                'completed_at' => $data['completed_at']->format('Y-m-d H:i'),
                'suggested_course' => $data['course'] ? new CourseListResource($data['course']) : null,
            ],
            'message' => 'Placement result retrieved successfully.',
            'meta'    => [],
        ]);
    }
}
