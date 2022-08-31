<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        /**
         * Register company partner from G-MET service
         */
        'PartnerRegisterRequest' => [
            'App\Listeners\PartnerRegisterRequestListener'
        ],

        'getUserByPhone' => [
            'App\Listeners\GetUserByPhoneListener'
        ],

        'logActivity' => [
            'App\Listeners\ActivityLogListener'
        ]
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
