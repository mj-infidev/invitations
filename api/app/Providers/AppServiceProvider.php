<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Limits per customer and per IP address (PLAN.md > Security). Each is
         * well above what one person building an invitation does, and well
         * below what a script would.
         */
        $customerAndIp = fn (Request $request, string $name, Limit ...$limits) => collect($limits)
            ->flatMap(fn (Limit $limit) => [
                (clone $limit)->by("{$name}:user:".$request->user()?->id),
                (clone $limit)->by("{$name}:ip:".$request->ip()),
            ])->all();

        // New drafts: 10 an hour.
        RateLimiter::for('create-invitation', fn (Request $request) => $customerAndIp($request, 'create', Limit::perHour(10)));

        // Photos: 30 a minute, 200 a day.
        RateLimiter::for('uploads', fn (Request $request) => $customerAndIp($request, 'uploads', Limit::perMinute(30), Limit::perDay(200)));

        // Autosave sends at most one save every 0.8 s while typing.
        RateLimiter::for('autosave', fn (Request $request) => $customerAndIp($request, 'autosave', Limit::perMinute(120)));
    }
}
