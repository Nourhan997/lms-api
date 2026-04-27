<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseFreeException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => 'This course is free and does not require payment.',
            'meta'    => [],
        ], 422);
    }
}
