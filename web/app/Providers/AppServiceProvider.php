<?php

namespace App\Providers;

use Carbon\Carbon;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;
use Dusterio\LumenPassport\LumenPassport;

class AppServiceProvider extends ServiceProvider
{
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
