<?php

namespace App\Providers;

use Dusterio\LumenPassport\LumenPassport;
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
}
