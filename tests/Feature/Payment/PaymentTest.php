<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Events\PaymentCompleted;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithToken(): array
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        return [$student, $token];
    }

    private function adminWithToken(): array
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        return [$admin, $token];
    }

    public function test_student_can_initiate_checkout_for_premium_course(): void
    {
        [$student, $token] = $this->studentWithToken();
        $course = Course::factory()->premium()->published()->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/checkout")
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('payments', [
            'user_id'   => $student->id,
            'course_id' => $course->id,
            'status'    => 'pending',
        ]);
    }

    public function test_student_cannot_checkout_free_course(): void
    {
        [, $token] = $this->studentWithToken();
        $course = Course::factory()->free()->published()->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/checkout")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_checkout_already_enrolled_course(): void
    {
        [$student, $token] = $this->studentWithToken();
        $course = Course::factory()->premium()->published()->create();

        Enrollment::factory()->create([
            'user_id'  => $student->id,
            'course_id' => $course->id,
        ]);

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$course->id}/checkout")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_confirm_demo_payment(): void
    {
        [$student, $token] = $this->studentWithToken();
        $course = Course::factory()->premium()->published()->create();

        $payment = Payment::factory()->forUser($student)->forCourse($course)->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/payments/{$payment->id}/confirm")
            ->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_enrollment_created_after_payment_confirmed(): void
    {
        [$student, $token] = $this->studentWithToken();
        $course = Course::factory()->premium()->published()->create();

        $payment = Payment::factory()->forUser($student)->forCourse($course)->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/payments/{$payment->id}/confirm")
            ->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'user_id'    => $student->id,
            'course_id'  => $course->id,
            'payment_id' => $payment->id,
        ]);
    }

    public function test_payment_receipt_email_queued_after_payment(): void
    {
        Notification::fake();

        [$student, $token] = $this->studentWithToken();
        $course = Course::factory()->premium()->published()->create();

        $payment = Payment::factory()->forUser($student)->forCourse($course)->create();

        $this->withToken($token)
            ->postJson("/api/v1/student/payments/{$payment->id}/confirm")
            ->assertStatus(201);

        Notification::assertSentTo($student, PaymentReceiptNotification::class);
    }

    public function test_admin_can_refund_payment(): void
    {
        [, $adminToken] = $this->adminWithToken();
        $student = User::factory()->student()->create();
        $course  = Course::factory()->premium()->published()->create();

        $payment = Payment::factory()->forUser($student)->forCourse($course)->completed()->create();

        Enrollment::factory()->create([
            'user_id'    => $student->id,
            'course_id'  => $course->id,
            'payment_id' => $payment->id,
        ]);

        $this->withToken($adminToken)
            ->postJson("/api/v1/admin/payments/{$payment->id}/refund")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'refunded');
    }

    public function test_enrollment_status_changes_to_refunded_after_refund(): void
    {
        [, $adminToken] = $this->adminWithToken();
        $student = User::factory()->student()->create();
        $course  = Course::factory()->premium()->published()->create();

        $payment = Payment::factory()->forUser($student)->forCourse($course)->completed()->create();

        $enrollment = Enrollment::factory()->create([
            'user_id'    => $student->id,
            'course_id'  => $course->id,
            'payment_id' => $payment->id,
        ]);

        $this->withToken($adminToken)
            ->postJson("/api/v1/admin/payments/{$payment->id}/refund")
            ->assertStatus(200);

        $this->assertDatabaseHas('enrollments', [
            'id'     => $enrollment->id,
            'status' => 'refunded',
        ]);
    }
}
