<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_quiz_id')->constrained('placement_quizzes')->restrictOnDelete();
            $table->integer('score_min');
            $table->integer('score_max');
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('label');
            $table->timestamps();

            $table->index('placement_quiz_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_results');
    }
};
