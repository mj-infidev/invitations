<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Requests from the Vue apps (SANCTUM_STATEFUL_DOMAINS) get the session cookie
        // and CSRF check: customers and admins sign in with cookies, not tokens.
        $middleware->statefulApi();

        $middleware->trustProxies(at: '*');

        // No web login page exists: API guests get a 401, never a redirect to route('login').
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every /api/* error is JSON, whatever the Accept header: 401 instead of a
        // redirect to a non-existent `login` route, 422 instead of a redirect back.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // Clean JSON 404 for the API — don't leak model class names or stack traces.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        // Other HTTP errors (419, 405, 429 …): message + status only, even with
        // APP_DEBUG=true, so the frontends get a stable shape.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') || $request->is('sanctum/*')) {
                return response()->json(
                    ['message' => $e->getMessage() ?: 'Request failed.'],
                    $e->getStatusCode(),
                    $e->getHeaders(),
                );
            }
        });
    })->create();
