<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminStudentDetailResource;
use App\Http\Resources\Admin\AdminStudentResource;
use App\Models\User;
use App\Services\Admin\AuditService;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        if ($request->has('has_enrollment')) {
            $filters['has_enrollment'] = $request->boolean('has_enrollment');
        }

        $students = $this->userManagementService->getAllStudents($filters);

        return response()->json([
            'success' => true,
            'data'    => AdminStudentResource::collection($students->items()),
            'message' => 'Students retrieved successfully.',
            'meta'    => [
                'total'        => $students->total(),
                'per_page'     => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page'    => $students->lastPage(),
            ],
        ]);
    }

    /**
     * @param  User         $user
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->userManagementService->getStudentProfile($user);

        return response()->json([
            'success' => true,
            'data'    => new AdminStudentDetailResource($user),
            'message' => 'Student profile retrieved successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * @param  User         $user
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function suspend(User $user): JsonResponse
    {
        $old  = ['is_active' => $user->is_active];
        $user = $this->userManagementService->suspend($user);
        $this->auditService->log('user.suspended', $user, $old, ['is_active' => false]);

        return response()->json([
            'success' => true,
            'data'    => new AdminStudentResource($user),
            'message' => 'Student suspended successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * @param  User         $user
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function activate(User $user): JsonResponse
    {
        $old  = ['is_active' => $user->is_active];
        $user = $this->userManagementService->activate($user);
        $this->auditService->log('user.activated', $user, $old, ['is_active' => true]);

        return response()->json([
            'success' => true,
            'data'    => new AdminStudentResource($user),
            'message' => 'Student activated successfully.',
            'meta'    => [],
        ]);
    }
}
