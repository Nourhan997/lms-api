<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\CertificateResource;
use App\Services\Certificate\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $certificates = $this->certificateService->getForUser($request->user());

        return response()->json([
            'success' => true,
            'data'    => CertificateResource::collection($certificates),
            'message' => 'Certificates retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function download(Request $request, string $uid): Response
    {
        $certificate = $this->certificateService->getByUid($uid);

        if (!$certificate || $certificate->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Certificate not found.',
                'meta'    => [],
            ], 404);
        }

        if (!$certificate->pdf_path || !Storage::disk('public')->exists($certificate->pdf_path)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Certificate PDF is not yet available. Please try again shortly.',
                'meta'    => [],
            ], 404);
        }

        return response()->download(
            Storage::disk('public')->path($certificate->pdf_path),
            "certificate-{$uid}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
