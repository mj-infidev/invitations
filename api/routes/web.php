<?php

use App\Http\Controllers\Auth\GoogleSignInController;
use App\Http\Controllers\InvitationPageController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// The page guests open. Everything else on oursite.com/ is the client app.
// No session: guests have nothing to sign in to, and with the database session
// driver every visit would otherwise write a row and set two cookies.
Route::get('/i/{slug}', [InvitationPageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class])
    ->name('invitation.show');

// Customer sign-in with Google. Full-page browser visits that need the session
// (Socialite's `state`), so they are web routes, though under /api/ like the rest
// of the API. Google's callback arrives from accounts.google.com, which the API's
// stateful check would not give a session.
Route::prefix('api/auth/google')
    ->controller(GoogleSignInController::class)
    ->middleware('throttle:20,1')
    ->group(function () {
        Route::get('/redirect', 'redirect')->name('google.redirect');
        Route::get('/callback', 'callback')->name('google.callback');
    });
