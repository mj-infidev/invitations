<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\InvitationMediaController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RazorpayWebhookController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

/*
 * API routes, all under /api. Customer routes use `auth:web`, admin routes (Step 6)
 * `auth:admin`, never `auth:sanctum` (config/auth.php). Google sign-in's two
 * browser routes are in routes/web.php. See PLAN.md > API routes.
 */

Route::get('/templates', [TemplateController::class, 'index']);
Route::get('/templates/{slug}', [TemplateController::class, 'show']);

Route::get('/me', [AuthController::class, 'me']);

// Razorpay, signed with the webhook secret; no session. PLAN.md > Payments.
Route::post('/webhooks/razorpay', RazorpayWebhookController::class)->middleware('throttle:120,1');

Route::middleware('auth:web')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/invitations', [InvitationController::class, 'index']);
    Route::post('/invitations', [InvitationController::class, 'store'])->middleware('throttle:create-invitation');
    Route::get('/invitations/{invitation}', [InvitationController::class, 'show']);
    Route::put('/invitations/{invitation}', [InvitationController::class, 'update'])->middleware('throttle:autosave');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy']);

    Route::post('/invitations/{invitation}/media', [InvitationMediaController::class, 'store'])->middleware('throttle:uploads');
    Route::delete('/invitations/{invitation}/media/{media}', [InvitationMediaController::class, 'destroy']);

    Route::post('/invitations/{invitation}/checkout', [PaymentController::class, 'checkout'])->middleware('throttle:checkout');
    Route::post('/payments/verify', [PaymentController::class, 'verify'])->middleware('throttle:checkout');
});
