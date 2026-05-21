<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'role'         => \App\Http\Middleware\CheckRole::class,
            'course.owner' => \App\Http\Middleware\CheckCourseOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $json = fn (int $status, string $message, array $extra = []) => response()->json(array_merge([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'meta'    => [],
        ], $extra), $status);

        $exceptions->render(function (ValidationException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(422, $e->getMessage(), ['errors' => $e->errors()]);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(429, 'Too many requests. Try again later.');
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(401, 'Unauthenticated.');
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(403, 'Unauthorized.');
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(403, 'Unauthorized.');
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(404, 'Resource not found.');
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(404, 'Resource not found.');
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) use ($json) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $json(500, 'A database error occurred.');
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) use ($json) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }
            if ($e instanceof HttpException) {
                return null;
            }
            return $json(500, 'An unexpected error occurred.');
        });
    })->create();
