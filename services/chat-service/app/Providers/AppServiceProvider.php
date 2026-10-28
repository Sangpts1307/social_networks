<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\RateLimiter::for('chat', function (\Illuminate\Http\Request $request) {
            $actor = $request->attributes->get('chat_actor');
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by('chat:'.$actor['organization_id'].':'.$actor['user_id']);
        });
    }
}
