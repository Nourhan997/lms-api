<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@lms.test'],
            [
                'name'               => 'LMS Admin',
                'password'           => Hash::make('password'),
                'role'               => UserRole::Admin,
                'preferred_language' => 'en',
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]
        );
    }
}
