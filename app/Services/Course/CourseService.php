<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\CourseStatus;
use App\Events\CourseCreated;
use App\Events\CoursePublished;
use App\Exceptions\CourseHasActiveEnrollmentsException;
use App\Exceptions\CourseNotFoundException;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class CourseService
{
    public function getPublished(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $page     = request()->query('page', 1);
        $cacheKey = 'courses.public.' . md5(serialize($filters)) . '.page.' . $page;

        return Cache::tags(['courses'])->remember($cacheKey, 300, function () use ($filters, $perPage) {
            return $this->buildPublishedQuery($filters)->paginate($perPage);
        });
    }

    public function getBySlug(string $slug): Course
    {
        $cacheKey = 'courses.slug.' . $slug;

        $course = Cache::tags(['courses'])->remember($cacheKey, 300, function () use ($slug) {
            return Course::published()
                ->where('slug', $slug)
                ->with(['instructor', 'category', 'sections' => function ($q): void {
                    $q->published()->with([
                        'lessons' => fn($lq) => $lq->select(['id', 'section_id', 'title', 'title_ar', 'order', 'duration_minutes', 'is_published'])->published(),
                        'quiz:id,section_id',
                    ]);
                }])
                ->withCount('enrollments')
                ->first();
        });

        if (! $course) {
            throw new CourseNotFoundException();
        }

        return $course;
    }

    public function getAllForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Course::query()
            ->with(['instructor:id,name', 'category:id,name'])
            ->withCount([
                'enrollments',
                'enrollments as completed_enrollments_count' => fn($q) => $q->completed(),
            ])
            ->withSum('payments', 'amount');

        $this->applyAdminFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    public function getForAdmin(Course $course): Course
    {
        $course->load(['instructor:id,name', 'category:id,name,name_ar']);
        $course->loadCount([
            'enrollments',
            'enrollments as completed_enrollments_count' => fn($q) => $q->completed(),
        ]);
        $course->loadSum('payments', 'amount');

        return $course;
    }

    public function create(array $data): Course
    {
        $course = Course::create($data);
        $course->refresh();
        event(new CourseCreated($course));

        return $course;
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);
        Cache::tags(['courses'])->flush();

        return $course->fresh(['instructor', 'category']);
    }

    public function delete(Course $course): void
    {
        if ($course->enrollments()->active()->exists()) {
            throw new CourseHasActiveEnrollmentsException();
        }

        Cache::tags(['courses'])->flush();
        $course->delete();
    }

    public function publish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::Published]);
        event(new CoursePublished($course));

        return $course->fresh();
    }

    public function archive(Course $course): Course
    {
        $course->update(['status' => CourseStatus::Archived]);
        Cache::tags(['courses'])->flush();

        return $course->fresh();
    }

    public function getNextCourse(Course $course): ?Course
    {
        if (! $course->next_course_id) {
            return null;
        }

        return Course::published()->find($course->next_course_id);
    }

    private function buildPublishedQuery(array $filters): Builder
    {
        $query = Course::published()
            ->with(['instructor:id,name', 'category:id,name,name_ar'])
            ->withCount('enrollments');

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(fn($q) => $q->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
        }

        if (! empty($filters['type'])) {
            $filters['type'] === 'free' ? $query->free() : $query->premium();
        }

        return $query->latest();
    }

    private function applyAdminFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(fn($q) => $q->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
        }
    }
}
