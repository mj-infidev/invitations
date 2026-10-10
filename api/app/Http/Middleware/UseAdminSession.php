<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel gets its own session, separate from customers'.
 *
 * Both apps call the same API (and locally share `localhost`), so by default
 * they'd share one cookie. For /api/admin/* this switches, before the session
 * starts: its own cookie name, sent only to /api/admin, a shorter life that
 * ends with the browser, and SameSite=Strict. Its CSRF cookie has its own name
 * too (App\Http\Middleware\ValidateCsrfToken), or the two apps would overwrite
 * each other's token.
 *
 * Global middleware, so it runs before the API group's session middleware.
 */
class UseAdminSession
{
    public const COOKIE = 'invitations_admin_session';

    public const CSRF_COOKIE = 'XSRF-TOKEN-ADMIN';

    public static function isAdminRequest(Request $request): bool
    {
        return $request->is('api/admin', 'api/admin/*');
    }

    /** The customer session's settings, as configured, captured before the first change. */
    private static ?array $defaults = null;

    public function handle(Request $request, Closure $next): Response
    {
        $admin = [
            'session.cookie' => self::COOKIE,
            'session.path' => '/api/admin',
            'session.lifetime' => 480, // minutes: a working day
            'session.expire_on_close' => true,
            'session.same_site' => 'strict',
        ];

        // Set both ways on every request: where one process serves many requests
        // (tests, a long-running server), a customer request after an admin one
        // must not inherit the admin cookie.
        self::$defaults ??= collect(array_keys($admin))->mapWithKeys(fn ($key) => [$key => config($key)])->all();
        $settings = self::isAdminRequest($request) ? $admin : self::$defaults;
        if (config('session.cookie') !== $settings['session.cookie']) {
            config($settings);
            // The session manager keeps its store (and the cookie name in it) once made.
            app('session')->forgetDrivers();
        }

        return $next($request);
    }
}
