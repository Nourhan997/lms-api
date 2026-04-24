<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminLessonController;
use App\Http\Controllers\Admin\AdminSectionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Instructor\InstructorContentController;
use App\Http\Controllers\Instructor\InstructorLessonController;
use App\Http\Controllers\Instructor\InstructorSectionController;
use App\Http\Controllers\Student\EnrollmentController;
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

        // Sections (nested under courses)
        Route::post('courses/{course}/sections/reorder', [AdminSectionController::class, 'reorder']);
        Route::get('courses/{course}/sections', [AdminSectionController::class, 'index']);
        Route::post('courses/{course}/sections', [AdminSectionController::class, 'store']);
        Route::put('courses/{course}/sections/{section}', [AdminSectionController::class, 'update']);
        Route::delete('courses/{course}/sections/{section}', [AdminSectionController::class, 'destroy']);

        // Lessons (nested under sections)
        Route::post('sections/{section}/lessons/reorder', [AdminLessonController::class, 'reorder']);
        Route::get('sections/{section}/lessons', [AdminLessonController::class, 'index']);
        Route::post('sections/{section}/lessons', [AdminLessonController::class, 'store']);
        Route::put('sections/{section}/lessons/{lesson}', [AdminLessonController::class, 'update']);
        Route::delete('sections/{section}/lessons/{lesson}', [AdminLessonController::class, 'destroy']);

        // Contents (nested under lessons)
        Route::post('lessons/{lesson}/contents/reorder', [AdminContentController::class, 'reorder']);
        Route::get('lessons/{lesson}/contents', [AdminContentController::class, 'index']);
        Route::post('lessons/{lesson}/contents', [AdminContentController::class, 'store']);
        Route::put('lessons/{lesson}/contents/{content}', [AdminContentController::class, 'update']);
        Route::delete('lessons/{lesson}/contents/{content}', [AdminContentController::class, 'destroy']);
    });

    Route::prefix('student')->middleware(['auth:sanctum', 'role:student'])->group(function (): void {
        Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        Route::get('enrollments/{enrollment}/progress', [EnrollmentController::class, 'progress']);
        Route::post('lessons/{lesson}/complete', [EnrollmentController::class, 'completeLesson']);
    });

    Route::prefix('instructor')->middleware(['auth:sanctum', 'role:instructor'])->group(function (): void {

        // Sections (nested under courses, ownership checked)
        Route::post('courses/{course}/sections/reorder', [InstructorSectionController::class, 'reorder'])
            ->middleware('course.owner');
        Route::get('courses/{course}/sections', [InstructorSectionController::class, 'index'])
            ->middleware('course.owner');
        Route::post('courses/{course}/sections', [InstructorSectionController::class, 'store'])
            ->middleware('course.owner');
        Route::put('courses/{course}/sections/{section}', [InstructorSectionController::class, 'update'])
            ->middleware('course.owner');
        Route::delete('courses/{course}/sections/{section}', [InstructorSectionController::class, 'destroy'])
            ->middleware('course.owner');

        // Lessons (nested under sections, ownership checked via section → course)
        Route::post('sections/{section}/lessons/reorder', [InstructorLessonController::class, 'reorder'])
            ->middleware('course.owner');
        Route::get('sections/{section}/lessons', [InstructorLessonController::class, 'index'])
            ->middleware('course.owner');
        Route::post('sections/{section}/lessons', [InstructorLessonController::class, 'store'])
            ->middleware('course.owner');
        Route::put('sections/{section}/lessons/{lesson}', [InstructorLessonController::class, 'update'])
            ->middleware('course.owner');
        Route::delete('sections/{section}/lessons/{lesson}', [InstructorLessonController::class, 'destroy'])
            ->middleware('course.owner');

        // Contents (nested under lessons, ownership checked via lesson → section → course)
        Route::post('lessons/{lesson}/contents/reorder', [InstructorContentController::class, 'reorder'])
            ->middleware('course.owner');
        Route::get('lessons/{lesson}/contents', [InstructorContentController::class, 'index'])
            ->middleware('course.owner');
        Route::post('lessons/{lesson}/contents', [InstructorContentController::class, 'store'])
            ->middleware('course.owner');
        Route::put('lessons/{lesson}/contents/{content}', [InstructorContentController::class, 'update'])
            ->middleware('course.owner');
        Route::delete('lessons/{lesson}/contents/{content}', [InstructorContentController::class, 'destroy'])
            ->middleware('course.owner');
    });
});
