<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_admin_can_list_students(): void
    {
        [$admin, $token] = $this->adminWithToken();
        User::factory()->student()->count(3)->create();

        $this->withToken($token)
            ->getJson('/api/v1/admin/students')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['meta' => ['total', 'per_page', 'current_page', 'last_page']]);
    }

    public function test_admin_can_view_student_profile(): void
    {
        [$admin, $token] = $this->adminWithToken();
        $student = User::factory()->student()->create();

        $this->withToken($token)
            ->getJson("/api/v1/admin/students/{$student->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'is_active', 'enrollments', 'quiz_attempts', 'payments', 'certificates'],
            ]);
    }

    public function test_admin_can_suspend_student(): void
    {
        [$admin, $token] = $this->adminWithToken();
        $student = User::factory()->student()->create();

        $this->withToken($token)
            ->postJson("/api/v1/admin/students/{$student->id}/suspend")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_active' => false]);
    }

    public function test_suspended_student_cannot_login(): void
    {
        [$admin, $adminToken] = $this->adminWithToken();
        $student = User::factory()->student()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->withToken($adminToken)
            ->postJson("/api/v1/admin/students/{$student->id}/suspend")
            ->assertStatus(200);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $student->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_admin_can_activate_student(): void
    {
        [$admin, $token] = $this->adminWithToken();
        $student = User::factory()->student()->inactive()->create();

        $this->withToken($token)
            ->postJson("/api/v1/admin/students/{$student->id}/activate")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_active' => true]);
    }
}
