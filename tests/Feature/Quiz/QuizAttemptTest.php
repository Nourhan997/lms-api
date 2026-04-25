<?php

declare(strict_types=1);

namespace Tests\Feature\Quiz;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledStudentWithQuiz(): array
    {
        $course   = Course::factory()->published()->free()->create();
        $section  = Section::factory()->create(['course_id' => $course->id]);
        $quiz     = Quiz::factory()->forSection($section)->create(['pass_score' => 70]);
        $question = QuizQuestion::factory()->forQuiz($quiz)->create(['type' => 'multiple_choice', 'order' => 1]);
        $correct  = QuizOption::factory()->forQuestion($question)->correct()->create(['order' => 1]);
        $wrong    = QuizOption::factory()->forQuestion($question)->create(['order' => 2]);

        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        Enrollment::create([
            'user_id'     => $student->id,
            'course_id'   => $course->id,
            'status'      => 'active',
            'enrolled_at' => now(),
        ]);

        return [$student, $token, $section, $quiz, $question, $correct, $wrong];
    }

    public function test_enrolled_student_can_view_quiz(): void
    {
        [, $token, $section] = $this->enrolledStudentWithQuiz();

        $this->withToken($token)
            ->getJson("/api/v1/student/sections/{$section->id}/quiz")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'title', 'pass_score', 'questions']]);
    }

    public function test_student_cannot_see_correct_answers_in_quiz(): void
    {
        [, $token, $section] = $this->enrolledStudentWithQuiz();

        $response = $this->withToken($token)
            ->getJson("/api/v1/student/sections/{$section->id}/quiz");

        $response->assertStatus(200);

        foreach ($response->json('data.questions') as $question) {
            foreach ($question['options'] as $option) {
                $this->assertArrayNotHasKey('is_correct', $option);
            }
        }
    }

    public function test_student_can_submit_quiz_attempt(): void
    {
        [$student, $token, , $quiz, $question, $correct] = $this->enrolledStudentWithQuiz();

        $response = $this->withToken($token)
            ->postJson("/api/v1/student/quizzes/{$quiz->id}/attempt", [
                'answers' => [
                    ['question_id' => $question->id, 'option_id' => $correct->id],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.passed', true);

        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
        ]);
    }

    public function test_quiz_score_calculated_correctly(): void
    {
        [, $token, , $quiz, $question, , $wrong] = $this->enrolledStudentWithQuiz();

        $this->withToken($token)
            ->postJson("/api/v1/student/quizzes/{$quiz->id}/attempt", [
                'answers' => [
                    ['question_id' => $question->id, 'option_id' => $wrong->id],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.passed', false);
    }

    public function test_student_can_retake_quiz(): void
    {
        [, $token, , $quiz, $question, $correct] = $this->enrolledStudentWithQuiz();

        $answers = ['answers' => [['question_id' => $question->id, 'option_id' => $correct->id]]];

        $this->withToken($token)->postJson("/api/v1/student/quizzes/{$quiz->id}/attempt", $answers);

        $this->withToken($token)
            ->postJson("/api/v1/student/quizzes/{$quiz->id}/attempt", $answers)
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_unenrolled_student_cannot_access_quiz(): void
    {
        $course  = Course::factory()->published()->free()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        Quiz::factory()->forSection($section)->create();

        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/student/sections/{$section->id}/quiz")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
