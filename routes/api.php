<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Student\PublicCourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    Route::prefix('public')->group(function (): void {
        Route::get('courses', [PublicCourseController::class, 'index']);
        Route::get('courses/{slug}', [PublicCourseController::class, 'show']);
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
        Route::apiResource('courses', AdminCourseController::class);
        Route::post('courses/{course}/publish', [AdminCourseController::class, 'publish']);
        Route::post('courses/{course}/archive', [AdminCourseController::class, 'archive']);
    });
});
