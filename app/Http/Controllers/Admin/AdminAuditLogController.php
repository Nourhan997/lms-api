<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Services\Admin\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['action', 'user_id', 'model_type', 'date_from', 'date_to']);
        $logs    = $this->auditService->getAll($filters);

        return response()->json([
            'success' => true,
            'data'    => AuditLogResource::collection($logs->items()),
            'message' => 'Audit logs retrieved successfully.',
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }
}
