<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'English Language',
                'name_ar'     => 'اللغة الإنجليزية',
                'slug'        => 'english-language',
                'description' => 'English language courses from beginner to advanced.',
                'is_active'   => true,
            ],
            [
                'name'        => 'French Language',
                'name_ar'     => 'اللغة الفرنسية',
                'slug'        => 'french-language',
                'description' => 'French language courses for all levels.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Business English',
                'name_ar'     => 'الإنجليزية للأعمال',
                'slug'        => 'business-english',
                'description' => 'Professional English for business communication.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Conversational Skills',
                'name_ar'     => 'مهارات المحادثة',
                'slug'        => 'conversational-skills',
                'description' => 'Improve your spoken language and conversational fluency.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Exam Preparation',
                'name_ar'     => 'التحضير للامتحانات',
                'slug'        => 'exam-preparation',
                'description' => 'Prepare for IELTS, TOEFL, DELF, and other language exams.',
                'is_active'   => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
