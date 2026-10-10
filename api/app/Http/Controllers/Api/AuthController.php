<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The signed-in customer, from the session cookie. Sign-in itself is the
 * Google redirect (Auth\GoogleSignInController).
 */
class AuthController extends Controller
{
    /**
     * GET /api/me. `data: null` (200, not 401) for a guest, so the client can
     * ask on every page load without an error in the console. An admin signed
     * in on the same cookie is not a customer: also null.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user('web');

        return response()->json(['data' => $user ? self::present($user) : null]);
    }

    /**
     * POST /api/auth/logout. Signs the customer out only: if an admin is signed
     * in on the same cookie, the session lives on with a new id.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if (Auth::guard('admin')->check()) {
            $request->session()->regenerate();
        } else {
            $request->session()->invalidate();
        }
        $request->session()->regenerateToken();

        return response()->json(['data' => null]);
    }

    public static function present(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'avatar_url', 'locale']);
    }
}
