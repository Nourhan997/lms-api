<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Exceptions\PlacementAlreadyCompletedException;
use App\Models\Notification;
use App\Models\PlacementQuiz;
use App\Models\PlacementQuizQuestion;
use App\Models\PlacementResult;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PlacementQuizService
{
    public function getAll(): LengthAwarePaginator
    {
        return PlacementQuiz::withCount('questions')->paginate(15);
    }

    public function getActive(string $subject): ?PlacementQuiz
    {
        return PlacementQuiz::active()
            ->where('subject', $subject)
            ->with('questions.options')
            ->first();
    }

    public function createQuiz(array $data): PlacementQuiz
    {
        return PlacementQuiz::create($data);
    }

    public function updateQuiz(PlacementQuiz $quiz, array $data): PlacementQuiz
    {
        $quiz->update($data);

        return $quiz->fresh();
    }

    public function deleteQuiz(PlacementQuiz $quiz): void
    {
        $quiz->results()->delete();
        $quiz->delete();
    }

    public function addQuestion(PlacementQuiz $quiz, array $data): PlacementQuizQuestion
    {
        $options  = $data['options'];
        $question = $quiz->questions()->create([
            'question' => $data['question'],
            'type'     => $data['type'],
            'order'    => $quiz->questions()->max('order') + 1,
        ]);

        foreach ($options as $index => $option) {
            $question->options()->create([
                'option_text' => $option['option_text'],
                'is_correct'  => $option['is_correct'],
                'order'       => $index + 1,
            ]);
        }

        return $question->load('options');
    }

    public function updateQuestion(PlacementQuizQuestion $question, array $data): PlacementQuizQuestion
    {
        $options = $data['options'] ?? null;
        unset($data['options']);
        $question->update($data);

        if ($options !== null) {
            $question->options()->delete();
            foreach ($options as $index => $option) {
                $question->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct'  => $option['is_correct'],
                    'order'       => $index + 1,
                ]);
            }
        }

        return $question->load('options');
    }

    public function deleteQuestion(PlacementQuizQuestion $question): void
    {
        $question->delete();
    }

    public function createResult(PlacementQuiz $quiz, array $data): PlacementResult
    {
        return $quiz->results()->create($data);
    }

    public function updateResult(PlacementResult $result, array $data): PlacementResult
    {
        $result->update($data);

        return $result->fresh();
    }

    public function deleteResult(PlacementResult $result): void
    {
        $result->delete();
    }

    public function getScores(): LengthAwarePaginator
    {
        return User::where('role', 'student')
            ->whereNotNull('placement_completed_at')
            ->with('suggestedCourse')
            ->paginate(20);
    }

    public function submitPlacementAttempt(User $user, PlacementQuiz $quiz, array $answers): array
    {
        if ($user->placement_completed_at !== null) {
            throw new PlacementAlreadyCompletedException();
        }

        $questions = $quiz->questions()->with('options')->get()->keyBy('id');
        [$correct, $total] = $this->gradeAnswers($questions, $answers);

        $score      = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $percentage = $total > 0 ? round($correct / $total * 100, 2) : 0.0;

        $result = $this->findMatchingResult($quiz, $score);

        $user->update([
            'placement_completed_at' => now(),
            'placement_score'        => $score,
            'placement_label'        => $result?->label,
            'suggested_course_id'    => $result?->course_id,
        ]);

        $this->createNotification($user, $result, $score);

        return [
            'score'      => $score,
            'percentage' => $percentage,
            'label'      => $result?->label ?? 'No level determined',
            'course'     => $result?->course,
        ];
    }

    public function getResult(User $user): ?array
    {
        if (!$user->placement_completed_at) {
            return null;
        }

        $user->load('suggestedCourse');

        return [
            'score'        => $user->placement_score,
            'label'        => $user->placement_label ?? 'No level determined',
            'completed_at' => $user->placement_completed_at,
            'course'       => $user->suggestedCourse,
        ];
    }

    private function gradeAnswers(Collection $questions, array $answers): array
    {
        $correct = 0;

        foreach ($answers as $answer) {
            $question = $questions->get($answer['question_id']);
            if (!$question) {
                continue;
            }
            if ($this->evaluateAnswer($question, $answer)) {
                $correct++;
            }
        }

        return [$correct, $questions->count()];
    }

    private function evaluateAnswer(PlacementQuizQuestion $question, array $answer): bool
    {
        $optionId = $answer['option_id'] ?? null;
        if ($optionId === null) {
            return false;
        }
        $option = $question->options->firstWhere('id', $optionId);

        return $option?->is_correct ?? false;
    }

    private function findMatchingResult(PlacementQuiz $quiz, int $score): ?PlacementResult
    {
        return $quiz->results()
            ->where('score_min', '<=', $score)
            ->where('score_max', '>=', $score)
            ->with('course')
            ->first();
    }

    private function createNotification(User $user, ?PlacementResult $result, int $score): void
    {
        $label = $result?->label ?? 'No level determined';

        Notification::create([
            'user_id' => $user->id,
            'type'    => 'placement_result',
            'title'   => 'Placement Test Complete',
            'body'    => "Your placement score is {$score}%. Level: {$label}.",
            'data'    => ['score' => $score, 'label' => $label, 'course_id' => $result?->course_id],
        ]);
    }
}
