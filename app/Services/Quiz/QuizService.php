<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Enums\QuizQuestionType;
use App\Exceptions\QuizAlreadyExistsException;
use App\Exceptions\QuizHasAttemptsException;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

class QuizService
{
    public function getForSection(Section $section): ?Quiz
    {
        return $section->quiz()->with('questions.options')->first();
    }

    public function createQuiz(Section $section, array $data): Quiz
    {
        if ($section->quiz()->exists()) {
            throw new QuizAlreadyExistsException();
        }

        return Quiz::create(['section_id' => $section->id, ...$data]);
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $quiz->update($data);

        return $quiz->fresh();
    }

    public function deleteQuiz(Quiz $quiz): void
    {
        if ($quiz->attempts()->exists()) {
            throw new QuizHasAttemptsException();
        }

        $quiz->delete();
    }

    public function addQuestion(Quiz $quiz, array $data): QuizQuestion
    {
        $options  = $data['options'];
        $question = $quiz->questions()->create([
            'question'    => $data['question'],
            'type'        => $data['type'],
            'explanation' => $data['explanation'] ?? null,
            'order'       => $quiz->questions()->max('order') + 1,
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

    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
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

    public function deleteQuestion(QuizQuestion $question): void
    {
        $question->delete();
    }

    public function submitAttempt(User $user, Quiz $quiz, array $answers): QuizAttempt
    {
        $questions = $quiz->questions()->with('options')->get()->keyBy('id');
        $attempt   = QuizAttempt::create([
            'user_id'      => $user->id,
            'quiz_id'      => $quiz->id,
            'score'        => 0,
            'passed'       => false,
            'completed_at' => now(),
        ]);

        [$correct, $total] = $this->gradeAnswers($attempt, $questions, $answers);

        $score  = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $passed = $score >= $quiz->pass_score;
        $attempt->update(['score' => $score, 'passed' => $passed]);

        return $attempt->load('answers');
    }

    public function getAttempts(User $user, Quiz $quiz): Collection
    {
        return $quiz->attempts()
            ->where('user_id', $user->id)
            ->with('answers')
            ->latest()
            ->get();
    }

    public function getBestAttempt(User $user, Quiz $quiz): ?QuizAttempt
    {
        return $quiz->attempts()
            ->where('user_id', $user->id)
            ->orderByDesc('score')
            ->first();
    }

    private function gradeAnswers(QuizAttempt $attempt, Collection $questions, array $answers): array
    {
        $correct = 0;

        foreach ($answers as $answer) {
            $question = $questions->get($answer['question_id']);
            if (!$question) {
                continue;
            }
            $isCorrect = $this->evaluateAnswer($question, $answer);
            if ($isCorrect) {
                $correct++;
            }
            QuizAnswer::create([
                'quiz_attempt_id'    => $attempt->id,
                'quiz_question_id'   => $question->id,
                'selected_option_id' => $answer['option_id'] ?? null,
                'text_answer'        => $answer['text_answer'] ?? null,
                'is_correct'         => $isCorrect,
            ]);
        }

        return [$correct, $questions->count()];
    }

    private function evaluateAnswer(QuizQuestion $question, array $answer): bool
    {
        return match ($question->type) {
            QuizQuestionType::MultipleChoice,
            QuizQuestionType::TrueFalse => $this->evaluateOptionAnswer($question, $answer['option_id'] ?? null),
            QuizQuestionType::FillBlank => $this->evaluateFillBlank($question, $answer['text_answer'] ?? null),
        };
    }

    private function evaluateOptionAnswer(QuizQuestion $question, ?int $optionId): bool
    {
        if ($optionId === null) {
            return false;
        }

        $option = $question->options->firstWhere('id', $optionId);

        return $option?->is_correct ?? false;
    }

    private function evaluateFillBlank(QuizQuestion $question, ?string $textAnswer): bool
    {
        if ($textAnswer === null) {
            return false;
        }

        $correctOption = $question->options->firstWhere('is_correct', true);

        if (!$correctOption) {
            return false;
        }

        return strcasecmp($textAnswer, $correctOption->option_text) === 0;
    }
}
