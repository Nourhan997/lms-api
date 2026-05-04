<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\InstructorWelcomeNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    public function getAllStudents(array $filters): LengthAwarePaginator
    {
        return User::where('role', UserRole::Student)
            ->withCount('enrollments')
            ->when($filters['search'] ?? null, fn ($q, $s) =>
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            )
            ->when($filters['status'] ?? null, fn ($q, $s) => match ($s) {
                'active'    => $q->where('is_active', true),
                'suspended' => $q->where('is_active', false),
                default     => $q,
            })
            ->when(isset($filters['has_enrollment']), fn ($q) =>
                $filters['has_enrollment'] ? $q->has('enrollments') : $q->doesntHave('enrollments')
            )
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function getStudentProfile(User $user): User
    {
        return $user->load([
            'enrollments.course:id,title,slug',
            'enrollments.lessonProgress',
            'enrollments.certificate:id,enrollment_id,certificate_uid,issued_at',
            'quizAttempts.quiz:id,title',
            'payments.course:id,title',
            'certificates.course:id,title',
        ]);
    }

    public function suspend(User $user): User
    {
        $user->update(['is_active' => false]);

        return $user->fresh();
    }

    public function activate(User $user): User
    {
        $user->update(['is_active' => true]);

        return $user->fresh();
    }

    public function getAllInstructors(): LengthAwarePaginator
    {
        return User::where('role', UserRole::Instructor)
            ->withCount('courses')
            ->withCount('courseEnrollments as total_enrollments')
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function createInstructor(array $data): User
    {
        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'bio'                => $data['bio'] ?? null,
            'role'               => UserRole::Instructor,
            'is_active'          => true,
            'preferred_language' => 'en',
            'email_verified_at'  => now(),
        ]);

        $user->notify(new InstructorWelcomeNotification());

        return $user;
    }

    public function updateInstructor(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }
}
