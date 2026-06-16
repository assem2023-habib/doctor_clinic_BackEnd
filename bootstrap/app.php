<?php

use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\HttpStatusEnum;
use App\Http\Middleware\AuthorizeByAttribute;
use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckStaffRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogApiBearerAndRequestDetails;
use App\Http\Middleware\NormalizeDuplicateBearerAuthorization;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ValidateApiBodySize;
use App\Http\Middleware\ValidateImageContent;
use App\Domains\Auth\Providers\LoginSecurityServiceProvider;
use App\Domains\Notifications\Providers\NotificationServiceProvider;
use App\Providers\AuthServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AuthServiceProvider::class,
        NotificationServiceProvider::class,
        LoginSecurityServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', SecurityHeaders::class);
        // $middleware->appendToGroup('api', 'throttle:api');
        $middleware->prependToGroup('api', ValidateApiBodySize::class);
        $middleware->prependToGroup('api', LogApiBearerAndRequestDetails::class);
        $middleware->prependToGroup('api', NormalizeDuplicateBearerAuthorization::class);

        $middleware->alias([
            'admin' => CheckAdminRole::class,
            'staff' => CheckStaffRole::class,
            'active' => EnsureUserIsActive::class,
            'image.content' => ValidateImageContent::class,
            'role.authorize' => AuthorizeByAttribute::class,
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

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 404,
                    'message' => class_basename($e->getModel()) . ' ' . __('not found'),
                    'data' => null,
                ], 404);
            }
        });

        $exceptions->render(function (ApiServiceException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    status: $e->getStatus(),
                    errorCode: $e->getErrorCode(),
                );
            }
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $status = $e->getStatusCode();
                $statusEnum = HttpStatusEnum::tryFrom($status) ?? HttpStatusEnum::InternalServerError;

                return ApiResponse::error(
                    message: $e->getMessage() ?: $statusEnum->label(),
                    status: $statusEnum,
                );
            }
        });
    })->create();
