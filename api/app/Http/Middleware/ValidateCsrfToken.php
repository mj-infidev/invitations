<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Laravel's CSRF check, with one change: on the admin API the readable token
 * cookie is `XSRF-TOKEN-ADMIN`, not `XSRF-TOKEN`. With one name for both apps,
 * each would overwrite the other's token and the next write would fail with 419.
 * The admin app sends it back in the usual X-XSRF-TOKEN header.
 *
 * Used by Sanctum's stateful API pipeline (config/sanctum.php).
 */
class ValidateCsrfToken extends Middleware
{
    protected function newCookie($request, $config)
    {
        $cookie = parent::newCookie($request, $config);

        if (! UseAdminSession::isAdminRequest($request)) {
            return $cookie;
        }

        // Path "/": the admin app's pages must be able to read it; the session
        // cookie itself stays limited to /api/admin.
        return new Cookie(
            UseAdminSession::CSRF_COOKIE,
            $cookie->getValue(),
            $cookie->getExpiresTime(),
            '/',
            $cookie->getDomain(),
            $cookie->isSecure(),
            false,
            false,
            $cookie->getSameSite(),
        );
    }
}
