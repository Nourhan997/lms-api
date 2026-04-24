<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('subject', ['english', 'french']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('subject');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_quizzes');
    }
};
