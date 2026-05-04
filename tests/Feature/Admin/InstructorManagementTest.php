<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\InstructorWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstructorManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_admin_can_create_instructor(): void
    {
        Notification::fake();

        [$admin, $token] = $this->adminWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/admin/instructors', [
                'name'     => 'Jane Instructor',
                'email'    => 'jane@instructor.com',
                'password' => 'password123',
                'bio'      => 'Experienced language tutor.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'jane@instructor.com');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@instructor.com',
            'role'  => 'instructor',
        ]);

        $instructor = User::where('email', 'jane@instructor.com')->first();
        Notification::assertSentTo($instructor, InstructorWelcomeNotification::class);
    }

    public function test_admin_can_suspend_instructor(): void
    {
        [$admin, $token] = $this->adminWithToken();
        $instructor = User::factory()->instructor()->create();

        $this->withToken($token)
            ->postJson("/api/v1/admin/instructors/{$instructor->id}/suspend")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $instructor->id, 'is_active' => false]);
    }
}
