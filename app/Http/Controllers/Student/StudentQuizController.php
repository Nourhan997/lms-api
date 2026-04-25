<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitQuizAttemptRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Models\Section;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Quiz\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentQuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizService,
        private readonly EnrollmentService $enrollmentService,
    ) {}

    public function show(Request $request, Section $section): JsonResponse
    {
        $section->load('course');
        $this->enrollmentService->getEnrollment($request->user(), $section->course);

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
            'data'    => new QuizResource($quiz),
            'message' => 'Quiz retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function attempt(SubmitQuizAttemptRequest $request, Quiz $quiz): JsonResponse
    {
        $quiz->load('section.course');
        $this->enrollmentService->getEnrollment($request->user(), $quiz->section->course);

        $attempt = $this->quizService->submitAttempt($request->user(), $quiz, $request->validated()['answers']);

        return response()->json([
            'success' => true,
            'data'    => new QuizAttemptResource($attempt),
            'message' => 'Quiz submitted successfully.',
            'meta'    => [],
        ], 201);
    }

    public function attempts(Request $request, Quiz $quiz): JsonResponse
    {
        $quiz->load('section.course');
        $this->enrollmentService->getEnrollment($request->user(), $quiz->section->course);

        $attempts = $this->quizService->getAttempts($request->user(), $quiz);

        return response()->json([
            'success' => true,
            'data'    => QuizAttemptResource::collection($attempts),
            'message' => 'Attempts retrieved successfully.',
            'meta'    => [],
        ]);
    }
}
