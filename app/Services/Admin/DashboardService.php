<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class DashboardService
{
    public function getOverview(): array
    {
        return Cache::tags(['dashboard'])->remember('dashboard.overview', 300, fn () => [
            'students'              => $this->studentStats(),
            'courses'               => $this->courseStats(),
            'enrollments'           => $this->enrollmentStats(),
            'revenue'               => $this->revenueStats(),
            'certificates_issued'   => Certificate::count(),
            'placement_tests_taken' => User::whereNotNull('placement_completed_at')->count(),
        ]);
    }

    public function getRevenueReport(string $period = 'monthly'): array
    {
        return Payment::where('status', PaymentStatus::Completed)
            ->when($period === 'daily', fn ($q) => $q->where('paid_at', '>=', Carbon::now()->subDays(30)))
            ->when($period === 'monthly', fn ($q) => $q->where('paid_at', '>=', Carbon::now()->subMonths(12)))
            ->selectRaw($this->buildPeriodSelectRaw($period, 'paid_at') . ', SUM(amount) as revenue, COUNT(*) as count')
            ->groupByRaw('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => ['period' => (string) $row->period, 'revenue' => (float) $row->revenue, 'count' => (int) $row->count])
            ->toArray();
    }

    public function getEnrollmentReport(string $period = 'monthly'): array
    {
        return Enrollment::when($period === 'daily', fn ($q) => $q->where('created_at', '>=', Carbon::now()->subDays(30)))
            ->when($period === 'monthly', fn ($q) => $q->where('created_at', '>=', Carbon::now()->subMonths(12)))
            ->selectRaw($this->buildPeriodSelectRaw($period, 'created_at') . ', COUNT(*) as count')
            ->groupByRaw('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => ['period' => (string) $row->period, 'revenue' => 0.0, 'count' => (int) $row->count])
            ->toArray();
    }

    public function getCompletionReport(): array
    {
        $courses = Course::withCount([
            'enrollments',
            'enrollments as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed),
        ])->get();

        $total     = $courses->sum('enrollments_count');
        $completed = $courses->sum('completed_count');

        return [
            'by_course'               => $this->mapCoursesForCompletion($courses),
            'overall_completion_rate' => $total > 0 ? round($completed / $total * 100, 2) : 0.0,
        ];
    }

    public function getTopCourses(int $limit = 10): array
    {
        return Course::withCount('enrollments')
            ->withCount(['enrollments as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed)])
            ->withSum(['payments as revenue' => fn ($q) => $q->where('status', PaymentStatus::Completed)], 'amount')
            ->orderByDesc('enrollments_count')
            ->limit($limit)
            ->get()
            ->map(fn ($course) => [
                'course_id'       => $course->id,
                'title'           => $course->title,
                'enrollments'     => $course->enrollments_count,
                'revenue'         => (float) ($course->revenue ?? 0),
                'completion_rate' => $course->enrollments_count > 0
                    ? round($course->completed_count / $course->enrollments_count * 100, 2)
                    : 0.0,
            ])
            ->toArray();
    }

    public function getStudentReport(array $filters): LengthAwarePaginator
    {
        return User::where('role', UserRole::Student)
            ->withCount('enrollments')
            ->withCount(['enrollments as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed)])
            ->withSum(['payments as total_spent' => fn ($q) => $q->where('status', PaymentStatus::Completed)], 'amount')
            ->when($filters['search'] ?? null, fn ($q, $s) =>
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            )
            ->when($filters['status'] ?? null, fn ($q, $s) => match ($s) {
                'active'    => $q->where('is_active', true),
                'suspended' => $q->where('is_active', false),
                default     => $q,
            })
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function getStudentsForExport(): LazyCollection
    {
        return User::where('role', UserRole::Student)
            ->withCount('enrollments')
            ->withCount(['enrollments as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed)])
            ->withSum(['payments as total_spent' => fn ($q) => $q->where('status', PaymentStatus::Completed)], 'amount')
            ->lazy();
    }

    public function getPaymentsForExport(): LazyCollection
    {
        return Payment::with(['user:id,name,email', 'course:id,title'])->lazy();
    }

    private function studentStats(): array
    {
        return [
            'total'          => User::where('role', UserRole::Student)->count(),
            'active'         => User::where('role', UserRole::Student)->where('is_active', true)->count(),
            'new_this_month' => User::where('role', UserRole::Student)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    private function courseStats(): array
    {
        return [
            'total'     => Course::count(),
            'published' => Course::where('status', CourseStatus::Published)->count(),
            'draft'     => Course::where('status', CourseStatus::Draft)->count(),
        ];
    }

    private function enrollmentStats(): array
    {
        return [
            'total'      => Enrollment::count(),
            'active'     => Enrollment::active()->count(),
            'completed'  => Enrollment::completed()->count(),
            'this_month' => Enrollment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    private function revenueStats(): array
    {
        $total    = (float) Payment::where('status', PaymentStatus::Completed)->sum('amount');
        $refunded = (float) Payment::where('status', PaymentStatus::Refunded)->sum('amount');

        return [
            'total'      => $total,
            'this_month' => (float) Payment::where('status', PaymentStatus::Completed)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
            'net'        => $total - $refunded,
        ];
    }

    private function mapCoursesForCompletion(Collection $courses): array
    {
        return $courses->map(fn ($course) => [
            'course_id'       => $course->id,
            'course_title'    => $course->title,
            'enrolled'        => $course->enrollments_count,
            'completed'       => $course->completed_count,
            'completion_rate' => $course->enrollments_count > 0
                ? round($course->completed_count / $course->enrollments_count * 100, 2)
                : 0.0,
        ])->values()->toArray();
    }

    private function buildPeriodSelectRaw(string $period, string $column): string
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return match ($period) {
                'daily'  => "DATE({$column}) as period",
                'yearly' => "YEAR({$column}) as period",
                default  => "DATE_FORMAT({$column}, '%Y-%m') as period",
            };
        }

        return match ($period) {
            'daily'  => "date({$column}) as period",
            'yearly' => "strftime('%Y', {$column}) as period",
            default  => "strftime('%Y-%m', {$column}) as period",
        };
    }
}
