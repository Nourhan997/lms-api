<?php

declare(strict_types=1);

namespace Tests\Feature\Quiz;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    private function instructorWithCourse(): array
    {
        $instructor = User::factory()->instructor()->create();
        $course     = Course::factory()->published()->create(['instructor_id' => $instructor->id]);
        $token      = $instructor->createToken('auth-token')->plainTextToken;

        return [$instructor, $course, $token];
    }

    public function test_instructor_can_create_quiz_for_section(): void
    {
        [, $course, $token] = $this->instructorWithCourse();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $response = $this->withToken($token)
            ->postJson("/api/v1/instructor/sections/{$section->id}/quiz", [
                'title'      => 'Grammar Quiz',
                'pass_score' => 70,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Grammar Quiz')
            ->assertJsonPath('data.pass_score', 70);

        $this->assertDatabaseHas('quizzes', [
            'section_id' => $section->id,
            'title'      => 'Grammar Quiz',
        ]);
    }

    public function test_instructor_cannot_create_duplicate_quiz(): void
    {
        [, $course, $token] = $this->instructorWithCourse();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->withToken($token)
            ->postJson("/api/v1/instructor/sections/{$section->id}/quiz", [
                'title' => 'First Quiz', 'pass_score' => 70,
            ]);

        $this->withToken($token)
            ->postJson("/api/v1/instructor/sections/{$section->id}/quiz", [
                'title' => 'Second Quiz', 'pass_score' => 80,
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_instructor_can_add_questions_with_options(): void
    {
        [, $course, $token] = $this->instructorWithCourse();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $quiz    = Quiz::factory()->forSection($section)->create();

        $response = $this->withToken($token)
            ->postJson("/api/v1/instructor/quizzes/{$quiz->id}/questions", [
                'question' => 'What is the capital of France?',
                'type'     => 'multiple_choice',
                'options'  => [
                    ['option_text' => 'Paris',  'is_correct' => true],
                    ['option_text' => 'London', 'is_correct' => false],
                    ['option_text' => 'Berlin', 'is_correct' => false],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.question', 'What is the capital of France?');

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id'  => $quiz->id,
            'question' => 'What is the capital of France?',
        ]);

        $this->assertDatabaseHas('quiz_options', [
            'option_text' => 'Paris',
            'is_correct'  => 1,
        ]);
    }

    public function test_instructor_cannot_delete_quiz_with_attempts(): void
    {
        [, $course, $token] = $this->instructorWithCourse();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $quiz    = Quiz::factory()->forSection($section)->create();
        $student = User::factory()->student()->create();

        QuizAttempt::create([
            'user_id'      => $student->id,
            'quiz_id'      => $quiz->id,
            'score'        => 80,
            'passed'       => true,
            'completed_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson("/api/v1/instructor/sections/{$section->id}/quiz")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
