<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Customer sign-in with Google, Socialite's redirect flow. Both routes are
 * full-page browser visits, not API calls, so they live in routes/web.php (with
 * a session: Socialite keeps its anti-forgery `state` there between the two)
 * while still answering under /api/auth/google/.
 */
class GoogleSignInController extends Controller
{
    private const RETURN_TO = 'signin.return_to';

    /** GET /api/auth/google/redirect?redirect=/path: off to Google. */
    public function redirect(Request $request): RedirectResponse
    {
        $request->session()->put(self::RETURN_TO, self::safePath($request->query('redirect')));

        return Socialite::driver('google')->redirect();
    }

    /**
     * GET /api/auth/google/callback: back from Google. Signs the customer in
     * with the session cookie and returns them to the client page they left.
     * Cancelling at Google, or any failure, returns them with ?signin=failed.
     */
    public function callback(Request $request): RedirectResponse
    {
        $returnTo = $request->session()->pull(self::RETURN_TO, '/');

        // ?error=access_denied when the customer cancels on Google's screen.
        if ($request->filled('error')) {
            return $this->toClient($returnTo, failed: true);
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // A stale or forged state, an expired code, or Google unreachable.
            report($e);

            return $this->toClient($returnTo, failed: true);
        }

        if (blank($google->getId())) {
            return $this->toClient($returnTo, failed: true);
        }

        $user = $this->findOrCreateCustomer($google);

        // Remembered: customers stay signed in past the 2-hour session.
        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return $this->toClient($returnTo);
    }

    /**
     * The customer for this Google account: by Google id, then by email (only
     * when Google has verified it, which links the two), else a new one.
     */
    private function findOrCreateCustomer(GoogleUser $google): User
    {
        $email = $google->getEmail();
        $emailVerified = ($google->user['email_verified'] ?? false) === true;

        $user = User::where('google_id', $google->getId())->first()
            ?? ($email && $emailVerified ? User::where('email', $email)->whereNull('google_id')->first() : null);

        if ($user === null) {
            return User::create([
                'name' => $google->getName() ?: ($email ? strstr($email, '@', true) : 'Customer'),
                // An unverified address isn't stored: it could belong to someone else.
                'email' => $emailVerified ? $email : null,
                'google_id' => $google->getId(),
                'avatar_url' => $google->getAvatar(),
            ]);
        }

        $user->forceFill([
            'google_id' => $google->getId(),
            'avatar_url' => $google->getAvatar() ?: $user->avatar_url,
            'email' => $user->email ?? ($emailVerified ? $email : null),
        ])->save();

        return $user;
    }

    /**
     * Only a path on the client site: a full address, "//host" or "/\host"
     * would let a link send someone to another site after signing in.
     */
    private static function safePath(mixed $path): string
    {
        return is_string($path) && preg_match('#^/(?![/\\\\])[^\s]*$#', $path) ? $path : '/';
    }

    private function toClient(string $path, bool $failed = false): RedirectResponse
    {
        if ($failed) {
            $path .= (str_contains($path, '?') ? '&' : '?').'signin=failed';
        }

        return redirect()->away(rtrim(config('invitations.client_url'), '/').$path);
    }
}
