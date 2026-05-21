<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPaymentResource;
use App\Models\Payment;
use App\Services\Admin\AuditService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only(['status', 'course_id', 'user_id', 'date_from', 'date_to']);
        $payments = $this->paymentService->getForAdmin($filters);

        return response()->json([
            'success' => true,
            'data'    => AdminPaymentResource::collection($payments),
            'message' => 'Payments retrieved successfully.',
            'meta'    => [
                'total'        => $payments->total(),
                'per_page'     => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * @param  Payment      $payment
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function refund(Payment $payment): JsonResponse
    {
        $payment = $this->paymentService->refund($payment);
        $this->auditService->log('payment.refunded', $payment);

        return response()->json([
            'success' => true,
            'data'    => new AdminPaymentResource($payment->load(['user', 'course'])),
            'message' => 'Payment refunded successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function report(): JsonResponse
    {
        $report = $this->paymentService->getReport();

        return response()->json([
            'success' => true,
            'data'    => $report,
            'message' => 'Payment report retrieved successfully.',
            'meta'    => [],
        ]);
    }
}
