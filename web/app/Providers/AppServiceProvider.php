<?php

namespace App\Providers;

use Carbon\Carbon;
use Dusterio\LumenPassport\LumenPassport;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        Passport::ignoreMigrations();

        Passport::tokensExpireIn(Carbon::now()->addDays(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
        Passport::personalAccessTokensExpireIn(Carbon::now()->addDays(30));

        LumenPassport::tokensExpireIn(Carbon::now()->addDays(1)); // Actor
        LumenPassport::allowMultipleTokens();
    }
}
