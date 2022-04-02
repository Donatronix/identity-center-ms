<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\RechargeBalanceTransactionListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'rechargeBalanceRequest' => [
            'App\Listeners\RechargeBalanceRequestListener',
        ],
        'rechargeBalanceTransaction' => [
            RechargeBalanceTransactionListener::class,
        ],
    ];
}
