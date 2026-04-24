<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentType;
use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonContent;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::firstOrCreate(
            ['email' => 'instructor@lms.test'],
            [
                'name'               => 'Sample Instructor',
                'password'           => Hash::make('password'),
                'role'               => UserRole::Instructor,
                'preferred_language' => 'en',
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]
        );

        $englishCat = Category::where('slug', 'english-language')->first();
        $frenchCat  = Category::where('slug', 'french-language')->first();
        $examCat    = Category::where('slug', 'exam-preparation')->first();

        $coursesData = [
            [
                'title'            => 'English for Beginners',
                'slug'             => 'english-for-beginners',
                'description'      => 'A complete beginner course for English language learners. Start from the very basics and build a strong foundation.',
                'level'            => CourseLevel::Beginner,
                'language'         => CourseLanguage::En,
                'price'            => 0,
                'status'           => CourseStatus::Published,
                'category_id'      => $englishCat?->id,
                'duration_minutes' => 300,
            ],
            [
                'title'            => 'French Intermediate',
                'slug'             => 'french-intermediate',
                'description'      => 'Take your French to the next level. This course covers grammar, vocabulary, and conversation for intermediate learners.',
                'level'            => CourseLevel::Intermediate,
                'language'         => CourseLanguage::Fr,
                'price'            => 49.99,
                'status'           => CourseStatus::Published,
                'category_id'      => $frenchCat?->id,
                'duration_minutes' => 480,
            ],
            [
                'title'            => 'IELTS Preparation',
                'slug'             => 'ielts-preparation',
                'description'      => 'Comprehensive IELTS preparation covering all four skills: reading, writing, listening, and speaking.',
                'level'            => CourseLevel::Advanced,
                'language'         => CourseLanguage::En,
                'price'            => 79.99,
                'status'           => CourseStatus::Draft,
                'category_id'      => $examCat?->id,
                'duration_minutes' => 600,
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = Course::firstOrCreate(
                ['slug' => $courseData['slug']],
                array_merge($courseData, [
                    'instructor_id' => $instructor->id,
                    'currency'      => 'OMR',
                ])
            );

            $this->seedSections($course);
        }
    }

    private function seedSections(Course $course): void
    {
        if ($course->sections()->exists()) {
            return;
        }

        $sections = [
            ['title' => 'Introduction', 'order' => 1],
            ['title' => 'Core Concepts', 'order' => 2],
            ['title' => 'Practice & Review', 'order' => 3],
        ];

        foreach ($sections as $sectionData) {
            $section = Section::create([
                'course_id'    => $course->id,
                'title'        => $sectionData['title'],
                'order'        => $sectionData['order'],
                'is_published' => true,
            ]);

            $this->seedLessons($section);
        }
    }

    private function seedLessons(Section $section): void
    {
        $lessons = [
            ['title' => 'Welcome & Overview', 'order' => 1, 'duration_minutes' => 10],
            ['title' => 'Key Vocabulary', 'order' => 2, 'duration_minutes' => 20],
            ['title' => 'Practice Exercise', 'order' => 3, 'duration_minutes' => 15],
        ];

        foreach ($lessons as $lessonData) {
            $lesson = Lesson::create([
                'section_id'       => $section->id,
                'title'            => $lessonData['title'],
                'order'            => $lessonData['order'],
                'duration_minutes' => $lessonData['duration_minutes'],
                'is_published'     => true,
            ]);

            LessonContent::create([
                'lesson_id' => $lesson->id,
                'type'      => ContentType::Text,
                'content'   => 'Sample content for ' . $lesson->title . '. This is placeholder content for the seed data.',
                'order'     => 1,
            ]);
        }
    }
}
