<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'placement_quiz_id',
        'score_min',
        'score_max',
        'course_id',
        'label',
    ];

    protected $casts = [
        'score_min' => 'integer',
        'score_max' => 'integer',
    ];

    public function placementQuiz(): BelongsTo
    {
        return $this->belongsTo(PlacementQuiz::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
