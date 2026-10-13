<?php

namespace App\Providers;

use App\Auth\RemoteIdentityUserProvider;
use App\Services\IdentityGatewayClient;
use Illuminate\Support\Facades\Auth;
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
        Auth::provider('remote-identity', fn () => new RemoteIdentityUserProvider(app(IdentityGatewayClient::class)));
    }
}
