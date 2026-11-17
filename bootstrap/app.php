<?php

use App\Http\Middleware\JsonBody;
use App\Http\Middleware\JwtAuth;
use App\Http\Middleware\RequireRole;
use App\Support\Api\ApiException;
use App\Support\Api\ErrorRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtAuth::class,
            'role' => RequireRole::class,
            'json.body' => JsonBody::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->dontReport(ApiException::class);

        // Semua error di /api/* memakai envelope standar SIA-API-INT (tanpa stack trace).
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return ErrorRenderer::render($e);
            }
        });
    })->create();
