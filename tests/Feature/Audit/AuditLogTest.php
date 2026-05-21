<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_admin_suspension_creates_audit_log(): void
    {
        [, $token] = $this->adminWithToken();
        $student   = User::factory()->student()->create();

        $this->withToken($token)
            ->postJson("/api/v1/admin/students/{$student->id}/suspend")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'   => 'user.suspended',
            'model_id' => $student->id,
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        [$admin, $token] = $this->adminWithToken();

        AuditLog::create([
            'user_id'    => $admin->id,
            'action'     => 'user.suspended',
            'model_type' => User::class,
            'model_id'   => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/admin/audit-logs')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'user', 'action', 'model_type', 'model_id', 'ip_address', 'created_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_audit_log_filtered_by_action(): void
    {
        [$admin, $token] = $this->adminWithToken();

        AuditLog::create(['user_id' => $admin->id, 'action' => 'user.suspended']);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'course.published']);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'user.suspended']);

        $this->withToken($token)
            ->getJson('/api/v1/admin/audit-logs?action=user.suspended')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
