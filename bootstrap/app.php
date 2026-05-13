<?php

use App\Domains\Shared\Responses\ApiResponse;
use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\LogApiBearerAndRequestDetails;
use App\Http\Middleware\NormalizeDuplicateBearerAuthorization;
use App\Http\Middleware\ValidateApiBodySize;
use App\Http\Middleware\ValidateImageContent;
use App\Providers\AuthServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('api', ValidateApiBodySize::class);
        $middleware->prependToGroup('api', LogApiBearerAndRequestDetails::class);
        $middleware->prependToGroup('api', NormalizeDuplicateBearerAuthorization::class);

        $middleware->alias([
            'admin' => CheckAdminRole::class,
            'image.content' => ValidateImageContent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::validationError('Validation failed', $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::unauthorized($e->getMessage());
            }
        });
    })->create();
