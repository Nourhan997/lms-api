<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Models\Lesson;
use App\Models\LessonContent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ContentService
{
    public function getForLesson(Lesson $lesson): Collection
    {
        return $lesson->contents()->get();
    }

    public function addContent(Lesson $lesson, array $data): LessonContent
    {
        $data['lesson_id'] = $lesson->id;
        $data['order']     = ($lesson->contents()->max('order') ?? 0) + 1;

        return LessonContent::create($data);
    }

    public function update(LessonContent $content, array $data): LessonContent
    {
        $content->update($data);

        return $content->fresh();
    }

    public function delete(LessonContent $content): void
    {
        if ($content->file_path) {
            Storage::disk('public')->delete($content->file_path);
        }

        $content->delete();
    }

    public function reorder(Lesson $lesson, array $contentIds): void
    {
        foreach ($contentIds as $index => $id) {
            $lesson->contents()->where('id', $id)->update(['order' => $index + 1]);
        }
    }

    public function handleFileUpload(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $type = $request->input('type');

        return $request->file($field)->store("lesson-content/{$type}", 'public');
    }
}
