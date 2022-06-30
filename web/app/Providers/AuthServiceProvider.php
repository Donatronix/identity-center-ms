<?php

namespace App\Providers;

use Dusterio\LumenPassport\LumenPassport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        LumenPassport::routes($this->app);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //Auth::user()->id = '10000000-1000-1000-1000-000000000001';
    }
}
