<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_present_on_all_responses(): void
    {
        $response = $this->getJson('/api/health')->assertStatus(200);

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_unauthenticated_request_returns_401_json(): void
    {
        $this->getJson('/api/v1/admin/dashboard')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_unauthorized_request_returns_403_json(): void
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_not_found_returns_404_json(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/admin/courses/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_validation_error_returns_422_with_errors(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/admin/courses', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_rate_limiting_returns_429(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => 'foo@bar.test',
                'password' => 'wrong',
            ]);
        }

        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Too many requests. Try again later.');
    }
}
