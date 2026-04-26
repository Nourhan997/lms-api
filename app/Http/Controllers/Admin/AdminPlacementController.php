<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlacementQuestionRequest;
use App\Http\Requests\Admin\StorePlacementQuizRequest;
use App\Http\Requests\Admin\StorePlacementResultRequest;
use App\Http\Requests\Admin\UpdatePlacementQuestionRequest;
use App\Http\Requests\Admin\UpdatePlacementQuizRequest;
use App\Http\Requests\Admin\UpdatePlacementResultRequest;
use App\Http\Resources\Admin\AdminPlacementQuizResource;
use App\Http\Resources\Admin\PlacementScoreResource;
use App\Models\PlacementQuiz;
use App\Models\PlacementQuizQuestion;
use App\Models\PlacementResult;
use App\Services\Quiz\PlacementQuizService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminPlacementController extends Controller
{
    public function __construct(
        private readonly PlacementQuizService $placementQuizService,
    ) {}

    public function index(): JsonResponse
    {
        $quizzes = $this->placementQuizService->getAll();

        return response()->json([
            'success' => true,
            'data'    => AdminPlacementQuizResource::collection($quizzes),
            'message' => 'Placement quizzes retrieved successfully.',
            'meta'    => [
                'total'        => $quizzes->total(),
                'per_page'     => $quizzes->perPage(),
                'current_page' => $quizzes->currentPage(),
                'last_page'    => $quizzes->lastPage(),
            ],
        ]);
    }

    public function store(StorePlacementQuizRequest $request): JsonResponse
    {
        $quiz = $this->placementQuizService->createQuiz($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminPlacementQuizResource($quiz),
            'message' => 'Placement quiz created successfully.',
            'meta'    => [],
        ], 201);
    }

    public function update(UpdatePlacementQuizRequest $request, PlacementQuiz $placementQuiz): JsonResponse
    {
        $quiz = $this->placementQuizService->updateQuiz($placementQuiz, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminPlacementQuizResource($quiz),
            'message' => 'Placement quiz updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(PlacementQuiz $placementQuiz): Response
    {
        $this->placementQuizService->deleteQuiz($placementQuiz);

        return response()->noContent();
    }

    public function storeQuestion(StorePlacementQuestionRequest $request, PlacementQuiz $placementQuiz): JsonResponse
    {
        $question = $this->placementQuizService->addQuestion($placementQuiz, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => $this->formatQuestion($question),
            'message' => 'Question added successfully.',
            'meta'    => [],
        ], 201);
    }

    public function updateQuestion(UpdatePlacementQuestionRequest $request, PlacementQuiz $placementQuiz, PlacementQuizQuestion $placementQuestion): JsonResponse
    {
        $question = $this->placementQuizService->updateQuestion($placementQuestion, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => $this->formatQuestion($question),
            'message' => 'Question updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroyQuestion(PlacementQuiz $placementQuiz, PlacementQuizQuestion $placementQuestion): Response
    {
        $this->placementQuizService->deleteQuestion($placementQuestion);

        return response()->noContent();
    }

    public function indexResults(PlacementQuiz $placementQuiz): JsonResponse
    {
        $results = $placementQuiz->results()->with('course')->get();

        return response()->json([
            'success' => true,
            'data'    => $results->map(fn($r) => $this->formatResult($r)),
            'message' => 'Results retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function storeResult(StorePlacementResultRequest $request, PlacementQuiz $placementQuiz): JsonResponse
    {
        $result = $this->placementQuizService->createResult($placementQuiz, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => $this->formatResult($result),
            'message' => 'Score range created successfully.',
            'meta'    => [],
        ], 201);
    }

    public function updateResult(UpdatePlacementResultRequest $request, PlacementQuiz $placementQuiz, PlacementResult $placementResult): JsonResponse
    {
        $result = $this->placementQuizService->updateResult($placementResult, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => $this->formatResult($result),
            'message' => 'Score range updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroyResult(PlacementQuiz $placementQuiz, PlacementResult $placementResult): Response
    {
        $this->placementQuizService->deleteResult($placementResult);

        return response()->noContent();
    }

    public function scores(): JsonResponse
    {
        $users = $this->placementQuizService->getScores();

        return response()->json([
            'success' => true,
            'data'    => PlacementScoreResource::collection($users),
            'message' => 'Placement scores retrieved successfully.',
            'meta'    => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    private function formatQuestion(PlacementQuizQuestion $question): array
    {
        return [
            'id'       => $question->id,
            'question' => $question->question,
            'type'     => $question->type,
            'order'    => $question->order,
            'options'  => $question->options->map(fn($o) => [
                'id'          => $o->id,
                'option_text' => $o->option_text,
                'is_correct'  => $o->is_correct,
                'order'       => $o->order,
            ]),
        ];
    }

    private function formatResult(PlacementResult $result): array
    {
        return [
            'id'        => $result->id,
            'score_min' => $result->score_min,
            'score_max' => $result->score_max,
            'label'     => $result->label,
            'course_id' => $result->course_id,
        ];
    }
}
