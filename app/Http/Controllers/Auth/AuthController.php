<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            'message' => 'Registration successful.',
            'meta'    => [],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            'message' => 'Login successful.',
            'meta'    => [],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Logged out successfully.',
            'meta'    => [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()),
            'message' => 'User retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'Profile updated successfully.',
            'meta'    => [],
        ]);
    }
}
