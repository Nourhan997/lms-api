<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('placement_completed_at')->nullable()->after('is_active');
            $table->integer('placement_score')->nullable()->after('placement_completed_at');
            $table->string('placement_label')->nullable()->after('placement_score');
            $table->foreignId('suggested_course_id')
                ->nullable()
                ->after('placement_label')
                ->constrained('courses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['suggested_course_id']);
            $table->dropColumn(['placement_completed_at', 'placement_score', 'placement_label', 'suggested_course_id']);
        });
    }
};
