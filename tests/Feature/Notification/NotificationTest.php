<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithToken(): array
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        return [$student, $token];
    }

    public function test_student_can_get_notifications(): void
    {
        [$student, $token] = $this->studentWithToken();

        Notification::factory()->forUser($student)->count(3)->create();

        $this->withToken($token)
            ->getJson('/api/v1/student/notifications')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['meta' => ['total', 'per_page', 'current_page', 'last_page', 'unread_count']]);
    }

    public function test_student_can_mark_notification_as_read(): void
    {
        [$student, $token] = $this->studentWithToken();

        $notification = Notification::factory()->forUser($student)->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/notifications/{$notification->id}/read")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        [$student, $token] = $this->studentWithToken();

        Notification::factory()->forUser($student)->count(3)->create();

        $this->withToken($token)
            ->postJson('/api/v1/student/notifications/read-all')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(
            0,
            Notification::where('user_id', $student->id)->whereNull('read_at')->count()
        );
    }

    public function test_student_gets_unread_count(): void
    {
        [$student, $token] = $this->studentWithToken();

        Notification::factory()->forUser($student)->count(2)->create();
        Notification::factory()->forUser($student)->read()->count(1)->create();

        $this->withToken($token)
            ->getJson('/api/v1/student/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 2);
    }

    public function test_student_cannot_read_other_users_notification(): void
    {
        [$student, $token] = $this->studentWithToken();
        $otherStudent      = User::factory()->student()->create();

        $notification = Notification::factory()->forUser($otherStudent)->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/notifications/{$notification->id}/read")
            ->assertStatus(403);
    }
}
