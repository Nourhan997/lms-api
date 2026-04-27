<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\CertificateVerificationResource;
use App\Services\Certificate\CertificateService;
use Illuminate\Http\JsonResponse;

class CertificateVerificationController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificateService,
    ) {}

    public function show(string $uid): JsonResponse
    {
        $certificate = $this->certificateService->verify($uid);

        return response()->json([
            'success' => true,
            'data'    => new CertificateVerificationResource($certificate),
            'message' => 'Certificate verified successfully.',
            'meta'    => [],
        ]);
    }
}
