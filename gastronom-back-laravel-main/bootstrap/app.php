<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function ($router) {
            Route::prefix('integration/1c')
                ->name('integration.1c.')
                ->withoutMiddleware([
                    \Illuminate\Cookie\Middleware\EncryptCookies::class,
                    \Illuminate\Session\Middleware\StartSession::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                ])
                ->group(base_path('routes/onec.php'));

            Route::prefix('integration/1c_v2')
                ->name('integration.1c_v2.')
                ->withoutMiddleware([
                    \Illuminate\Cookie\Middleware\EncryptCookies::class,
                    \Illuminate\Session\Middleware\StartSession::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                ])
                ->group(base_path('routes/onec_v2.php'));

            Route::prefix('api/payments')
                ->name('api.payments.')
                ->withoutMiddleware([
                    \Illuminate\Cookie\Middleware\EncryptCookies::class,
                    \Illuminate\Session\Middleware\StartSession::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                    \Illuminate\Auth\Middleware\Authenticate::class,
                ])
                ->group(base_path('routes/payment.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->removeFromGroup('api', RedirectIfAuthenticated::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, $request) {
            return ApiResponse::unprocessableEntity($e->getMessage())
                ->additional(['errors' => $e->errors()]);
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::unauthorized($e->getMessage());
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return ApiResponse::notFound()->additional([
                'ids' => $e->getIds(),
            ]);
        });

        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::laravelError(
                    $e->getMessage() ?: 'HTTP error',
                    $e->getStatusCode()
                );
            }

            abort($e->getStatusCode(), $e->getMessage());
        });
    })
    ->create();
