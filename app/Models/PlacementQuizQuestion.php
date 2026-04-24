<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuizQuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlacementQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'placement_quiz_id',
        'question',
        'type',
        'order',
    ];

    protected $casts = [
        'type'  => QuizQuestionType::class,
        'order' => 'integer',
    ];

    public function placementQuiz(): BelongsTo
    {
        return $this->belongsTo(PlacementQuiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PlacementQuizOption::class)->orderBy('order');
    }
}
