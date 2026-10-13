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
        RateLimiter::for('social', function (Request $request) {
            $actor = $request->attributes->get('social_actor');

            return Limit::perMinute(120)->by('social:'.$actor['organization_id'].':'.$actor['user_id']);
        });
    }
}
