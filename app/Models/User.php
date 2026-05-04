<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourseLanguage;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'preferred_language',
        'is_active',
        'placement_completed_at',
        'placement_score',
        'placement_label',
        'suggested_course_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'role'                   => UserRole::class,
            'preferred_language'     => CourseLanguage::class,
            'is_active'              => 'boolean',
            'placement_completed_at' => 'datetime',
            'placement_score'        => 'integer',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function courseEnrollments(): HasManyThrough
    {
        return $this->hasManyThrough(Enrollment::class, Course::class, 'instructor_id', 'course_id');
    }

    public function suggestedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'suggested_course_id');
    }
}
