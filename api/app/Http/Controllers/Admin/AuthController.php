<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin sign-in, on the admin panel's own session cookie (UseAdminSession):
 * email and password from the `admins` table, no "remember me", 8 hours.
 */
class AuthController extends Controller
{
    private const ATTEMPTS = 5;

    /** GET /api/admin/csrf-cookie: starts the admin session and sets XSRF-TOKEN-ADMIN. */
    public function csrfCookie(): Response
    {
        return response()->noContent();
    }

    /**
     * POST /api/admin/login. A wrong email and a wrong password get the same
     * answer, so admin emails can't be discovered; 5 failures per email and
     * address lock it for a minute.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $key = 'admin-login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => "Too many attempts. Please try again in {$seconds} seconds."])->status(429);
        }

        if (! Auth::guard('admin')->attempt($credentials)) {
            RateLimiter::hit($key, 60);
            AdminLog::record(null, 'admin.login_failed', details: ['email' => Str::lower($credentials['email'])]);
            throw ValidationException::withMessages(['email' => "Those details don't match an admin account."]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        AdminLog::record($admin, 'admin.login');

        return response()->json(['data' => self::present($admin)]);
    }

    /** POST /api/admin/logout */
    public function logout(Request $request): JsonResponse
    {
        AdminLog::record($request->user('admin'), 'admin.logout');

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => null]);
    }

    /** GET /api/admin/me: the signed-in admin, or null (200, not 401). */
    public function me(Request $request): JsonResponse
    {
        $admin = $request->user('admin');

        return response()->json(['data' => $admin ? self::present($admin) : null]);
    }

    private static function present(Admin $admin): array
    {
        return $admin->only(['id', 'name', 'email']);
    }
}
