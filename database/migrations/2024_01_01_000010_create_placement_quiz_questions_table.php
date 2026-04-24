<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_quiz_id')->constrained('placement_quizzes')->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'true_false']);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['placement_quiz_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_quiz_questions');
    }
};
