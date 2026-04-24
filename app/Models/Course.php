<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'category_id',
        'next_course_id',
        'title',
        'title_ar',
        'slug',
        'description',
        'description_ar',
        'thumbnail',
        'level',
        'language',
        'price',
        'currency',
        'status',
        'duration_minutes',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'status'   => CourseStatus::class,
        'level'    => CourseLevel::class,
        'language' => CourseLanguage::class,
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nextCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'next_course_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Section::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('price', 0);
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('price', '>', 0);
    }

    public function scopeByLanguage(Builder $query, CourseLanguage $language): Builder
    {
        return $query->where('language', $language);
    }
}
