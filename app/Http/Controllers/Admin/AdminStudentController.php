<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminStudentDetailResource;
use App\Http\Resources\Admin\AdminStudentResource;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only(['search', 'status']);
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

    public function suspend(User $user): JsonResponse
    {
        $user = $this->userManagementService->suspend($user);

        return response()->json([
            'success' => true,
            'data'    => new AdminStudentResource($user),
            'message' => 'Student suspended successfully.',
            'meta'    => [],
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        $user = $this->userManagementService->activate($user);

        return response()->json([
            'success' => true,
            'data'    => new AdminStudentResource($user),
            'message' => 'Student activated successfully.',
            'meta'    => [],
        ]);
    }
}
