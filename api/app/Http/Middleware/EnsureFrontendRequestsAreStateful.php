<?php

namespace App\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as Middleware;

/**
 * Sanctum's session-for-the-Vue-apps middleware, which sets every session
 * cookie to SameSite=Lax. The admin panel's own session (UseAdminSession) is
 * kept at Strict: it is only ever called from the admin app itself.
 */
class EnsureFrontendRequestsAreStateful extends Middleware
{
    protected function configureSecureCookieSessions()
    {
        parent::configureSecureCookieSessions();

        if (UseAdminSession::isAdminRequest(request())) {
            config(['session.same_site' => 'strict']);
        }
    }
}
