<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Support\Collection;

class LessonService
{
    public function getForSection(Section $section): Collection
    {
        return $section->lessons()->withCount('contents')->get();
    }

    public function create(Section $section, array $data): Lesson
    {
        $data['section_id'] = $section->id;
        $data['order']      = ($section->lessons()->max('order') ?? 0) + 1;

        return Lesson::create($data);
    }

    public function update(Lesson $lesson, array $data): Lesson
    {
        $lesson->update($data);

        return $lesson->fresh();
    }

    public function delete(Lesson $lesson): void
    {
        $lesson->delete();
    }

    public function reorder(Section $section, array $lessonIds): void
    {
        foreach ($lessonIds as $index => $id) {
            $section->lessons()->where('id', $id)->update(['order' => $index + 1]);
        }
    }
}
