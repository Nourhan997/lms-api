<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminLessonController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPlacementController;
use App\Http\Controllers\Admin\AdminSectionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Instructor\InstructorContentController;
use App\Http\Controllers\Instructor\InstructorLessonController;
use App\Http\Controllers\Instructor\InstructorSectionController;
use App\Http\Controllers\Instructor\InstructorQuizController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\CertificateVerificationController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\PlacementController;
use App\Http\Controllers\Student\PublicCourseController;
use App\Http\Controllers\Student\StudentQuizController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    Route::prefix('public')->group(function (): void {
        Route::get('courses', [PublicCourseController::class, 'index']);
        Route::get('courses/{slug}', [PublicCourseController::class, 'show']);
        Route::get('certificates/{uid}', [CertificateVerificationController::class, 'show']);
        Route::get('blog', [BlogController::class, 'index']);
        Route::get('blog/{slug}', [BlogController::class, 'show']);
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

        // Placement quizzes
        Route::get('placement-quizzes', [AdminPlacementController::class, 'index']);
        Route::post('placement-quizzes', [AdminPlacementController::class, 'store']);
        Route::put('placement-quizzes/{placementQuiz}', [AdminPlacementController::class, 'update']);
        Route::delete('placement-quizzes/{placementQuiz}', [AdminPlacementController::class, 'destroy']);

        // Placement questions
        Route::post('placement-quizzes/{placementQuiz}/questions', [AdminPlacementController::class, 'storeQuestion']);
        Route::put('placement-quizzes/{placementQuiz}/questions/{placementQuestion}', [AdminPlacementController::class, 'updateQuestion']);
        Route::delete('placement-quizzes/{placementQuiz}/questions/{placementQuestion}', [AdminPlacementController::class, 'destroyQuestion']);

        // Placement results (score ranges)
        Route::get('placement-quizzes/{placementQuiz}/results', [AdminPlacementController::class, 'indexResults']);
        Route::post('placement-quizzes/{placementQuiz}/results', [AdminPlacementController::class, 'storeResult']);
        Route::put('placement-quizzes/{placementQuiz}/results/{placementResult}', [AdminPlacementController::class, 'updateResult']);
        Route::delete('placement-quizzes/{placementQuiz}/results/{placementResult}', [AdminPlacementController::class, 'destroyResult']);

        // Placement scores report
        Route::get('placement-scores', [AdminPlacementController::class, 'scores']);

        // Payments (report before {payment} to avoid route conflict)
        Route::get('payments/report', [AdminPaymentController::class, 'report']);
        Route::get('payments', [AdminPaymentController::class, 'index']);
        Route::post('payments/{payment}/refund', [AdminPaymentController::class, 'refund']);

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

        // Blog posts
        Route::get('blog', [AdminBlogController::class, 'index']);
        Route::post('blog', [AdminBlogController::class, 'store']);
        Route::get('blog/{post}', [AdminBlogController::class, 'show']);
        Route::put('blog/{post}', [AdminBlogController::class, 'update']);
        Route::delete('blog/{post}', [AdminBlogController::class, 'destroy']);
        Route::post('blog/{post}/publish', [AdminBlogController::class, 'publish']);
        Route::post('blog/{post}/unpublish', [AdminBlogController::class, 'unpublish']);
    });

    Route::prefix('student')->middleware(['auth:sanctum', 'role:student'])->group(function (): void {
        Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
        Route::post('courses/{course}/checkout', [PaymentController::class, 'checkout']);
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        Route::get('enrollments/{enrollment}/progress', [EnrollmentController::class, 'progress']);
        Route::post('lessons/{lesson}/complete', [EnrollmentController::class, 'completeLesson']);

        // Payments
        Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm']);
        Route::get('payments', [PaymentController::class, 'index']);

        // Certificates
        Route::get('certificates', [CertificateController::class, 'index']);
        Route::get('certificates/{uid}/download', [CertificateController::class, 'download']);

        // Quiz routes
        Route::get('sections/{section}/quiz', [StudentQuizController::class, 'show']);
        Route::post('quizzes/{quiz}/attempt', [StudentQuizController::class, 'attempt']);
        Route::get('quizzes/{quiz}/attempts', [StudentQuizController::class, 'attempts']);

        // Placement routes (result before {subject} to avoid conflict)
        Route::get('placement/result', [PlacementController::class, 'result']);
        Route::get('placement/{subject}', [PlacementController::class, 'show']);
        Route::post('placement/{subject}/submit', [PlacementController::class, 'submit']);

        // Notifications (unread-count before {notification} to avoid conflict)
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
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

        // Quiz (nested under sections, ownership checked)
        Route::get('sections/{section}/quiz', [InstructorQuizController::class, 'show'])
            ->middleware('course.owner');
        Route::post('sections/{section}/quiz', [InstructorQuizController::class, 'store'])
            ->middleware('course.owner');
        Route::put('sections/{section}/quiz', [InstructorQuizController::class, 'update'])
            ->middleware('course.owner');
        Route::delete('sections/{section}/quiz', [InstructorQuizController::class, 'destroy'])
            ->middleware('course.owner');

        // Questions (nested under quizzes, ownership checked via quiz → section → course)
        Route::post('quizzes/{quiz}/questions', [InstructorQuizController::class, 'storeQuestion'])
            ->middleware('course.owner');
        Route::put('quizzes/{quiz}/questions/{question}', [InstructorQuizController::class, 'updateQuestion'])
            ->middleware('course.owner');
        Route::delete('quizzes/{quiz}/questions/{question}', [InstructorQuizController::class, 'destroyQuestion'])
            ->middleware('course.owner');
    });
});
