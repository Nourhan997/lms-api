<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuizQuestionRequest;
use App\Http\Requests\Instructor\StoreQuizRequest;
use App\Http\Requests\Instructor\UpdateQuizQuestionRequest;
use App\Http\Requests\Instructor\UpdateQuizRequest;
use App\Http\Resources\Admin\AdminQuizResource;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Services\Quiz\QuizService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InstructorQuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizService
    ) {}

    public function show(Section $section): JsonResponse
    {
        $quiz = $this->quizService->getForSection($section);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'No quiz found for this section.',
                'meta'    => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AdminQuizResource($quiz),
            'message' => 'Quiz retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function store(StoreQuizRequest $request, Section $section): JsonResponse
    {
        $quiz = $this->quizService->createQuiz($section, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminQuizResource($quiz),
            'message' => 'Quiz created successfully.',
            'meta'    => [],
        ], 201);
    }

    public function update(UpdateQuizRequest $request, Section $section): JsonResponse
    {
        $quiz = $section->quiz;

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'No quiz found for this section.',
                'meta'    => [],
            ], 404);
        }

        $quiz = $this->quizService->updateQuiz($quiz, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminQuizResource($quiz),
            'message' => 'Quiz updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(Section $section): Response
    {
        $quiz = $section->quiz;

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'No quiz found for this section.',
                'meta'    => [],
            ], 404);
        }

        $this->quizService->deleteQuiz($quiz);

        return response()->noContent();
    }

    public function storeQuestion(StoreQuizQuestionRequest $request, Quiz $quiz): JsonResponse
    {
        $question = $this->quizService->addQuestion($quiz, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => $this->formatQuestion($question),
            'message' => 'Question added successfully.',
            'meta'    => [],
        ], 201);
    }

    public function updateQuestion(UpdateQuizQuestionRequest $request, Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        $question = $this->quizService->updateQuestion($question, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => $this->formatQuestion($question),
            'message' => 'Question updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $question): Response
    {
        $this->quizService->deleteQuestion($question);

        return response()->noContent();
    }

    private function formatQuestion(QuizQuestion $question): array
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
}
