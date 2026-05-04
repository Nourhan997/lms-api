<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Http\Resources\Admin\AdminInstructorResource;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\JsonResponse;

class AdminInstructorController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

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

    public function suspend(User $user): JsonResponse
    {
        $instructor = $this->userManagementService->suspend($user);

        return response()->json([
            'success' => true,
            'data'    => new AdminInstructorResource($instructor),
            'message' => 'Instructor suspended successfully.',
            'meta'    => [],
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        $instructor = $this->userManagementService->activate($user);

        return response()->json([
            'success' => true,
            'data'    => new AdminInstructorResource($instructor),
            'message' => 'Instructor activated successfully.',
            'meta'    => [],
        ]);
    }
}
