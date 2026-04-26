<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlacementAlreadyCompletedException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => 'You have already completed the placement test.',
            'meta'    => [],
        ], 409);
    }
}
