<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentCompleted;
use App\Events\PaymentRefunded;
use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\CourseFreeException;
use App\Exceptions\PaymentNotCompletedException;
use App\Exceptions\PendingPaymentExistsException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentService
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService,
    ) {}

    public function initiate(User $user, Course $course): Payment
    {
        if ($course->price <= 0) {
            throw new CourseFreeException();
        }

        if ($this->enrollmentService->isEnrolled($user, $course)) {
            throw new AlreadyEnrolledException();
        }

        if (Payment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', PaymentStatus::Pending)
            ->exists()) {
            throw new PendingPaymentExistsException();
        }

        return Payment::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'amount'    => $course->price,
            'currency'  => $course->currency,
            'status'    => PaymentStatus::Pending,
            'gateway'   => 'demo',
        ]);
    }

    public function complete(Payment $payment): Enrollment
    {
        $payment->update([
            'status'  => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);

        $enrollment = $this->enrollmentService->enrollFromPayment($payment);

        event(new PaymentCompleted($payment->fresh()->load(['user', 'course'])));

        return $enrollment;
    }

    public function refund(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Completed) {
            throw new PaymentNotCompletedException();
        }

        $payment->update(['status' => PaymentStatus::Refunded]);

        $payment->loadMissing('enrollment');

        if ($payment->enrollment) {
            $payment->enrollment->update(['status' => EnrollmentStatus::Refunded]);
        }

        event(new PaymentRefunded($payment->fresh()));

        return $payment->fresh();
    }

    public function getForUser(User $user): Collection
    {
        return $user->payments()->with('course')->latest()->get();
    }

    public function getForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Payment::with(['user', 'course'])->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function getReport(): array
    {
        $totalRevenue  = Payment::where('status', PaymentStatus::Completed)->sum('amount');
        $totalRefunded = Payment::where('status', PaymentStatus::Refunded)->sum('amount');

        $revenueByCourse = Payment::where('status', PaymentStatus::Completed)
            ->with('course:id,title,slug')
            ->selectRaw('course_id, SUM(amount) as revenue, COUNT(*) as payment_count')
            ->groupBy('course_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'course_id'     => $p->course_id,
                'course_title'  => $p->course?->title,
                'revenue'       => (float) $p->revenue,
                'payment_count' => (int) $p->payment_count,
            ]);

        return [
            'total_revenue'   => (float) $totalRevenue,
            'total_refunded'  => (float) $totalRefunded,
            'net_revenue'     => (float) ($totalRevenue - $totalRefunded),
            'payments_count'  => [
                'pending'   => Payment::where('status', PaymentStatus::Pending)->count(),
                'completed' => Payment::where('status', PaymentStatus::Completed)->count(),
                'refunded'  => Payment::where('status', PaymentStatus::Refunded)->count(),
            ],
            'revenue_by_course' => $revenueByCourse,
        ];
    }
}
