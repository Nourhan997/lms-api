<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role->value !== $role) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'You do not have permission to access this resource.',
                'meta'    => [],
            ], 403);
        }

        return $next($request);
    }
}
