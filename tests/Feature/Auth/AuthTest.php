<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'preferred_language'    => 'en',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.role', 'student')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

        Notification::assertSentTo(
            User::where('email', 'jane@example.com')->first(),
            WelcomeNotification::class
        );
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->student()->create([
            'email'    => 'student@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'student@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'student@example.com')
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->student()->create([
            'email'    => 'student@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'student@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->student()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissing(['password']);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/auth/profile', [
                'name'               => 'Updated Name',
                'preferred_language' => 'ar',
                'bio'                => 'A short bio.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.preferred_language', 'ar');

        $this->assertDatabaseHas('users', [
            'id'  => $user->id,
            'bio' => 'A short bio.',
        ]);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->student()->create([
            'email'    => 'target@example.com',
            'password' => bcrypt('secret'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email'    => 'target@example.com',
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'target@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401);

        $this->putJson('/api/v1/auth/profile', [
            'name'               => 'Test',
            'preferred_language' => 'en',
        ])->assertStatus(401);
    }
}
