<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Http\Resources\Admin\AdminInstructorResource;
use App\Models\User;
use App\Services\Admin\AuditService;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\JsonResponse;

class AdminInstructorController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $instructors = $this->userManagementService->getAllInstructors();

        return response()->json([
            'success' => true,
            'data'    => AdminInstructorResource::collection($instructors->items()),
            'message' => 'Instructors retrieved successfully.',
            'meta'    => [
                'total'        => $instructors->total(),
                'per_page'     => $instructors->perPage(),
                'current_page' => $instructors->currentPage(),
                'last_page'    => $instructors->lastPage(),
            ],
        ]);
    }

    /**
     * @param  StoreInstructorRequest $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreInstructorRequest $request): JsonResponse
    {
        $instructor = $this->userManagementService->createInstructor($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminInstructorResource($instructor),
            'message' => 'Instructor account created successfully.',
            'meta'    => [],
        ], 201);
    }

    /**
     * @param  UpdateInstructorRequest $request
     * @param  User                    $user
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(UpdateInstructorRequest $request, User $user): JsonResponse
    {
        $instructor = $this->userManagementService->updateInstructor($user, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminInstructorResource($instructor),
            'message' => 'Instructor updated successfully.',
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
        $old        = ['is_active' => $user->is_active];
        $instructor = $this->userManagementService->suspend($user);
        $this->auditService->log('user.suspended', $instructor, $old, ['is_active' => false]);

        return response()->json([
            'success' => true,
            'data'    => new AdminInstructorResource($instructor),
            'message' => 'Instructor suspended successfully.',
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
        $old        = ['is_active' => $user->is_active];
        $instructor = $this->userManagementService->activate($user);
        $this->auditService->log('user.activated', $instructor, $old, ['is_active' => true]);

        return response()->json([
            'success' => true,
            'data'    => new AdminInstructorResource($instructor),
            'message' => 'Instructor activated successfully.',
            'meta'    => [],
        ]);
    }
}
