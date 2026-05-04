<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_admin_can_get_dashboard_overview(): void
    {
        [, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'students'    => ['total', 'active', 'new_this_month'],
                    'courses'     => ['total', 'published', 'draft'],
                    'enrollments' => ['total', 'active', 'completed', 'this_month'],
                    'revenue'     => ['total', 'this_month', 'net'],
                    'certificates_issued',
                    'placement_tests_taken',
                ],
            ]);
    }

    public function test_dashboard_returns_correct_student_count(): void
    {
        [, $token] = $this->adminWithToken();
        User::factory()->student()->count(3)->create();

        $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('data.students.total', 3);
    }

    public function test_dashboard_returns_correct_revenue(): void
    {
        [, $token] = $this->adminWithToken();

        Payment::factory()->completed()->create(['amount' => 100.00]);

        $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('data.revenue.total', 100);
    }

    public function test_admin_can_get_revenue_report(): void
    {
        [, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->getJson('/api/v1/admin/reports/revenue?period=monthly')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_get_completion_report(): void
    {
        [, $token] = $this->adminWithToken();

        $course   = Course::factory()->published()->create();
        $student  = User::factory()->student()->create();
        Enrollment::factory()->completed()->create([
            'user_id'   => $student->id,
            'course_id' => $course->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/admin/reports/completions')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['by_course', 'overall_completion_rate'],
            ]);
    }

    public function test_admin_can_export_students_csv(): void
    {
        [, $token] = $this->adminWithToken();
        User::factory()->student()->count(2)->create();

        $this->withToken($token)
            ->get('/api/v1/admin/reports/export/students')
            ->assertOk()
            ->assertDownload('students.csv');
    }

    public function test_admin_can_export_payments_csv(): void
    {
        [, $token] = $this->adminWithToken();
        Payment::factory()->completed()->count(2)->create();

        $this->withToken($token)
            ->get('/api/v1/admin/reports/export/payments')
            ->assertOk()
            ->assertDownload('payments.csv');
    }
}
