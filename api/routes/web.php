<?php

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
