<?php

use App\Http\EnsureUserIsClubManager;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureClubAccess;
use App\Http\Middleware\EnsureJsonErrorShape;
use App\Http\Middleware\EnsureQueueIsRunning;
use App\Http\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            Inertia\Middleware::class,
            SetLocale::class,
        ]);

        $middleware->redirectGuestsTo(fn ($request) => $request->is('club*')
            ? route('club.login')
            : route('admin.login')
        );

        $middleware->alias([
            'queue.running' => EnsureQueueIsRunning::class,
            'club' => EnsureUserIsClubManager::class,
            'club.access' => EnsureClubAccess::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->api(append: [
            CheckMaintenanceMode::class,
            EnsureJsonErrorShape::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthenticated',
                    'errors' => null,
                ], 401);
            }

            if ($e instanceof AuthorizationException || $e instanceof SpatieUnauthorizedException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Forbidden',
                    'errors' => null,
                ], 403);
            }

            if ($e instanceof ModelNotFoundException) {
                $model = class_basename($e->getModel());

                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found",
                    'errors' => null,
                ], 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Endpoint not found',
                    'errors' => null,
                ], 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Method not allowed',
                    'errors' => null,
                ], 405);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Too many requests',
                    'errors' => null,
                ], 429);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP error',
                    'errors' => null,
                ], $e->getStatusCode());
            }

            Log::error('api.unhandled_exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'path' => $request->path(),
                'user_id' => optional($request->user())->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->hasDebugModeEnabled() ? $e->getMessage() : 'Server error',
                'errors' => app()->hasDebugModeEnabled() ? [
                    'exception' => $e::class,
                    'file' => $e->getFile().':'.$e->getLine(),
                ] : null,
            ], 500);
        });
    })->create();
