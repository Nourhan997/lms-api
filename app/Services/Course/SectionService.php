<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Exceptions\SectionHasPublishedLessonsException;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Support\Collection;

class SectionService
{
    public function getForCourse(Course $course): Collection
    {
        return $course->sections()
            ->withCount('lessons')
            ->with('quiz:id,section_id')
            ->get();
    }

    public function create(Course $course, array $data): Section
    {
        $data['course_id'] = $course->id;
        $data['order']     = ($course->sections()->max('order') ?? 0) + 1;

        return Section::create($data);
    }

    public function update(Section $section, array $data): Section
    {
        $section->update($data);

        return $section->fresh();
    }

    public function delete(Section $section): void
    {
        if ($section->lessons()->published()->exists()) {
            throw new SectionHasPublishedLessonsException();
        }

        $section->delete();
    }

    public function reorder(Course $course, array $sectionIds): void
    {
        foreach ($sectionIds as $index => $id) {
            $course->sections()->where('id', $id)->update(['order' => $index + 1]);
        }
    }
}
