<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('next_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('language', ['en', 'ar', 'fr'])->default('en');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('OMR');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('duration_minutes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('level');
            $table->index('language');
            $table->index('slug');
            $table->index(['status', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
