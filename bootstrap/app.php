<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
       // Handle 404: Model not found
    $exceptions->renderable(function (
        Illuminate\Database\Eloquent\ModelNotFoundException $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'error' => 'Resource not found.'
            ], 404);
        }
    });

    // Handle 422: ValidationException
    $exceptions->renderable(function (
        Illuminate\Validation\ValidationException $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'error'  => 'Validation failed.',
                'fields' => $e->errors(),
            ], 422);
        }
    });

    // Handle all other exceptions (500)
    $exceptions->renderable(function (
        Throwable $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'error' => 'Server error.',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    });

})->create();
