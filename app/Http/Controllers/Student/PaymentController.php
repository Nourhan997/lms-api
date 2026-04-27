<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\EnrollmentResource;
use App\Http\Resources\Student\PaymentResource;
use App\Models\Course;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function checkout(Request $request, Course $course): JsonResponse
    {
        $payment = $this->paymentService->initiate($request->user(), $course);

        $payment->load('course');

        return response()->json([
            'success' => true,
            'data'    => new PaymentResource($payment),
            'message' => 'Payment initiated. Please confirm to complete your enrollment.',
            'meta'    => [],
        ], 201);
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Payment not found.',
                'meta'    => [],
            ], 403);
        }

        $enrollment = $this->paymentService->complete($payment);

        return response()->json([
            'success' => true,
            'data'    => new EnrollmentResource($enrollment),
            'message' => 'Payment confirmed. You are now enrolled.',
            'meta'    => [],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->getForUser($request->user());

        return response()->json([
            'success' => true,
            'data'    => PaymentResource::collection($payments),
            'message' => 'Payment history retrieved successfully.',
            'meta'    => [],
        ]);
    }
}
