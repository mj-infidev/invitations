<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
 * API routes, all under /api. Customer routes use `auth:web`, admin routes (Step 6)
 * `auth:admin`, never `auth:sanctum` (config/auth.php). Google sign-in's two
 * browser routes are in routes/web.php. See PLAN.md > API routes.
 */

Route::get('/me', [AuthController::class, 'me']);

Route::middleware('auth:web')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
