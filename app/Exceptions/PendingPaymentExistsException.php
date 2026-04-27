<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingPaymentExistsException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => 'A pending payment already exists for this course.',
            'meta'    => [],
        ], 409);
    }
}
