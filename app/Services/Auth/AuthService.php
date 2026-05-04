<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Exceptions\AccountSuspendedException;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'preferred_language' => $data['preferred_language'],
            'role'               => UserRole::Student,
            'is_active'          => true,
            'email_verified_at'  => now(),
        ]);

        $user->notify(new WelcomeNotification());

        return [
            'user'  => $user,
            'token' => $this->generateToken($user),
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw new AccountSuspendedException();
        }

        return [
            'user'  => $user,
            'token' => $this->generateToken($user),
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function generateToken(User $user): string
    {
        $abilities = match ($user->role) {
            UserRole::Admin      => ['*'],
            UserRole::Instructor => ['courses:manage', 'students:view'],
            UserRole::Student    => ['courses:view', 'quizzes:attempt', 'enrollments:manage'],
        };

        return $user->createToken('auth-token', $abilities)->plainTextToken;
    }
}
