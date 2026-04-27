<?php

declare(strict_types=1);

namespace App\Services\Enrollment;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Events\CourseCompleted;
use App\Events\StudentEnrolled;
use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\EnrollmentNotFoundException;
use App\Exceptions\PaymentRequiredException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Payment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

class EnrollmentService
{
    public function enroll(User $user, Course $course): Enrollment
    {
        if ($course->status !== CourseStatus::Published) {
            throw new \App\Exceptions\CourseNotFoundException();
        }

        if ($this->isEnrolled($user, $course)) {
            throw new AlreadyEnrolledException();
        }

        if ($course->price > 0) {
            throw new PaymentRequiredException();
        }

        $enrollment = Enrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'status'      => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        event(new StudentEnrolled($course, $user));

        return $enrollment->load('course');
    }

    public function getMyEnrollments(User $user): Collection
    {
        return $user->enrollments()
            ->with([
                'course' => fn($q) => $q
                    ->with(['category', 'instructor:id,name'])
                    ->withCount('lessons'),
            ])
            ->withCount([
                'lessonProgress as completed_lessons_count' => fn($q) => $q->where('is_completed', true),
            ])
            ->get();
    }

    public function getEnrollment(User $user, Course $course): Enrollment
    {
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->with(['course.category', 'course.instructor:id,name'])
            ->first();

        if (!$enrollment) {
            throw new EnrollmentNotFoundException();
        }

        return $enrollment;
    }

    public function completeLesson(User $user, Lesson $lesson, Enrollment $enrollment): LessonProgress
    {
        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'enrollment_id' => $enrollment->id,
                'is_completed'  => true,
                'completed_at'  => now(),
            ]
        );

        if ($enrollment->status !== EnrollmentStatus::Completed && $this->isAllLessonsCompleted($enrollment)) {
            $this->completeCourse($enrollment);
        }

        return $progress;
    }

    public function completeCourse(Enrollment $enrollment): Enrollment
    {
        $enrollment->update([
            'status'       => EnrollmentStatus::Completed,
            'completed_at' => now(),
        ]);

        event(new CourseCompleted($enrollment));

        return $enrollment->fresh();
    }

    public function getCourseProgress(User $user, Enrollment $enrollment): array
    {
        $enrollment->load(['course.sections.lessons', 'lessonProgress']);

        $completedMap     = $enrollment->lessonProgress->keyBy('lesson_id');
        $totalLessons     = 0;
        $completedLessons = 0;
        $sections         = [];

        foreach ($enrollment->course->sections as $section) {
            [$sectionData, $sTotal, $sCompleted] = $this->buildSectionProgress($section, $completedMap);
            $sections[]        = $sectionData;
            $totalLessons      += $sTotal;
            $completedLessons  += $sCompleted;
        }

        $percentage = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100, 2) : 0.0;

        return [
            'total_lessons'     => $totalLessons,
            'completed_lessons' => $completedLessons,
            'percentage'        => $percentage,
            'sections'          => $sections,
        ];
    }

    public function isEnrolled(User $user, Course $course): bool
    {
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    public function enrollFromPayment(Payment $payment): Enrollment
    {
        $enrollment = Enrollment::create([
            'user_id'    => $payment->user_id,
            'course_id'  => $payment->course_id,
            'payment_id' => $payment->id,
            'status'     => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $payment->loadMissing(['course', 'user']);
        event(new StudentEnrolled($payment->course, $payment->user));

        return $enrollment->load('course');
    }

    private function buildSectionProgress(Section $section, Collection $completedMap): array
    {
        $sectionLessons = [];
        $total          = 0;
        $completed      = 0;

        foreach ($section->lessons as $lesson) {
            $progress    = $completedMap->get($lesson->id);
            $isCompleted = $progress?->is_completed ?? false;
            $total++;

            if ($isCompleted) {
                $completed++;
            }

            $sectionLessons[] = [
                'id'           => $lesson->id,
                'title'        => $lesson->title,
                'is_completed' => $isCompleted,
                'completed_at' => $progress?->completed_at?->toDateTimeString(),
            ];
        }

        return [
            ['id' => $section->id, 'title' => $section->title, 'lessons' => $sectionLessons],
            $total,
            $completed,
        ];
    }

    private function isAllLessonsCompleted(Enrollment $enrollment): bool
    {
        $courseId  = $enrollment->course_id;
        $total     = Lesson::whereHas('section', fn($q) => $q->where('course_id', $courseId))->count();
        $completed = $enrollment->lessonProgress()->where('is_completed', true)->count();

        return $total > 0 && $total === $completed;
    }
}
