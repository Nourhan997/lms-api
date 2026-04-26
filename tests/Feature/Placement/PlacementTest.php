<?php

declare(strict_types=1);

namespace Tests\Feature\Placement;

use App\Models\Course;
use App\Models\PlacementQuiz;
use App\Models\PlacementQuizOption;
use App\Models\PlacementQuizQuestion;
use App\Models\PlacementResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacementTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    private function studentWithToken(): array
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        return [$student, $token];
    }

    private function setupPlacementQuiz(string $subject = 'english'): array
    {
        $quiz     = PlacementQuiz::factory()->create(['subject' => $subject, 'is_active' => true]);
        $question = PlacementQuizQuestion::factory()->forQuiz($quiz)->create(['type' => 'multiple_choice', 'order' => 1]);
        $correct  = PlacementQuizOption::factory()->forQuestion($question)->correct()->create(['order' => 1]);
        $wrong    = PlacementQuizOption::factory()->forQuestion($question)->create(['order' => 2]);

        return [$quiz, $question, $correct, $wrong];
    }

    public function test_admin_can_create_placement_quiz(): void
    {
        [, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/admin/placement-quizzes', [
                'title'   => 'English Placement Test',
                'subject' => 'english',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'English Placement Test')
            ->assertJsonPath('data.subject', 'english');

        $this->assertDatabaseHas('placement_quizzes', ['title' => 'English Placement Test']);
    }

    public function test_admin_can_configure_score_ranges(): void
    {
        [, $token] = $this->adminWithToken();
        [$quiz]    = $this->setupPlacementQuiz();
        $course    = Course::factory()->published()->create();

        $this->withToken($token)
            ->postJson("/api/v1/admin/placement-quizzes/{$quiz->id}/results", [
                'score_min' => 0,
                'score_max' => 50,
                'label'     => 'Beginner — A1',
                'course_id' => $course->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Beginner — A1')
            ->assertJsonPath('data.score_min', 0)
            ->assertJsonPath('data.score_max', 50);

        $this->assertDatabaseHas('placement_results', [
            'placement_quiz_id' => $quiz->id,
            'label'             => 'Beginner — A1',
        ]);
    }

    public function test_student_can_take_placement_test(): void
    {
        [$quiz, $question, $correct] = $this->setupPlacementQuiz();
        [, $token]                   = $this->studentWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/student/placement/english/submit', [
                'answers' => [['question_id' => $question->id, 'option_id' => $correct->id]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['score', 'percentage', 'label', 'suggested_course']]);
    }

    public function test_placement_suggests_correct_course_based_on_score(): void
    {
        [$quiz, $question, $correct] = $this->setupPlacementQuiz();
        $course = Course::factory()->published()->create();

        PlacementResult::factory()->forQuiz($quiz)->create([
            'score_min' => 80,
            'score_max' => 100,
            'label'     => 'Advanced — C1',
            'course_id' => $course->id,
        ]);

        [$student, $token] = $this->studentWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/student/placement/english/submit', [
                'answers' => [['question_id' => $question->id, 'option_id' => $correct->id]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.label', 'Advanced — C1')
            ->assertJsonPath('data.suggested_course.id', $course->id);

        $this->assertDatabaseHas('users', [
            'id'                  => $student->id,
            'suggested_course_id' => $course->id,
        ]);
    }

    public function test_student_cannot_retake_placement_test(): void
    {
        [$quiz, $question, $correct] = $this->setupPlacementQuiz();
        [, $token]                   = $this->studentWithToken();

        $answers = ['answers' => [['question_id' => $question->id, 'option_id' => $correct->id]]];

        $this->withToken($token)
            ->postJson('/api/v1/student/placement/english/submit', $answers)
            ->assertStatus(200);

        $this->withToken($token)
            ->postJson('/api/v1/student/placement/english/submit', $answers)
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_student_gets_null_suggestion_if_no_range_matches(): void
    {
        [$quiz, $question, $correct] = $this->setupPlacementQuiz();
        [, $token]                   = $this->studentWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/student/placement/english/submit', [
                'answers' => [['question_id' => $question->id, 'option_id' => $correct->id]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.suggested_course', null);
    }
}
