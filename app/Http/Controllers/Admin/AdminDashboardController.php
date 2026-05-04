<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminStudentReportResource;
use App\Http\Resources\Admin\DashboardResource;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function overview(): JsonResponse
    {
        $data = $this->dashboardService->getOverview();

        return response()->json([
            'success' => true,
            'data'    => new DashboardResource($data),
            'message' => 'Dashboard overview retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getRevenueReport($request->get('period', 'monthly'));

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Revenue report retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function enrollments(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getEnrollmentReport($request->get('period', 'monthly'));

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Enrollment report retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function completions(): JsonResponse
    {
        $data = $this->dashboardService->getCompletionReport();

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Completion report retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function topCourses(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->get('limit', 10), 1), 50);
        $data  = $this->dashboardService->getTopCourses($limit);

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Top courses retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $report = $this->dashboardService->getStudentReport(
            $request->only(['search', 'status', 'date_from', 'date_to'])
        );

        return response()->json([
            'success' => true,
            'data'    => AdminStudentReportResource::collection($report->items()),
            'message' => 'Student report retrieved successfully.',
            'meta'    => [
                'total'        => $report->total(),
                'per_page'     => $report->perPage(),
                'current_page' => $report->currentPage(),
                'last_page'    => $report->lastPage(),
            ],
        ]);
    }

    public function exportStudents(): StreamedResponse
    {
        $students = $this->dashboardService->getStudentsForExport();

        return response()->streamDownload(function () use ($students): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'email', 'enrollments', 'completed', 'spent', 'joined']);
            foreach ($students as $s) {
                fputcsv($out, [$s->name, $s->email, $s->enrollments_count ?? 0, $s->completed_count ?? 0, $s->total_spent ?? 0, $s->created_at->format('Y-m-d')]);
            }
            fclose($out);
        }, 'students.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPayments(): StreamedResponse
    {
        $payments = $this->dashboardService->getPaymentsForExport();

        return response()->streamDownload(function () use ($payments): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['student', 'course', 'amount', 'status', 'date']);
            foreach ($payments as $p) {
                fputcsv($out, [$p->user?->name, $p->course?->title, $p->amount, $p->status->value, $p->paid_at?->format('Y-m-d') ?? '']);
            }
            fclose($out);
        }, 'payments.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
