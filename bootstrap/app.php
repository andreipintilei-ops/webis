<?php

use App\Actions\Seo\HandleNotFound;
use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectLegacyIds;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'can.access-admin' => EnsureCanAccessAdmin::class,
        ]);

        $middleware->web(prepend: [
            RedirectLegacyIds::class,
        ], append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A request about to 404 may be an old WordPress address: a 301 (or a
        // 410) from HandleNotFound, or null for the plain errors/404 page.
        $exceptions->render(
            fn (NotFoundHttpException $e, Request $request) => app(HandleNotFound::class)($request),
        );
    })->create();
