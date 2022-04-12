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

        // $this->app['auth']->viaRequest('api', function ($request) {
        //     return new GenericUser([
        //         'id' => (string) $request->header('user-id'),
        //         'username' => (string) $request->header('username', null)
        //     ]);
        // });
    }
}
