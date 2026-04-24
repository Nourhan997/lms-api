<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizQuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse      = 'true_false';
    case FillBlank      = 'fill_blank';
}
