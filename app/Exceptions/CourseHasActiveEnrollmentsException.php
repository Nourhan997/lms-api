<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseHasActiveEnrollmentsException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => 'Cannot delete a course with active enrollments.',
            'meta'    => [],
        ], 422);
    }
}
