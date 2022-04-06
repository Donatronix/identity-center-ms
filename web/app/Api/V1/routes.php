<?php

use App\Api\V1\Controllers\OneStepId\VerifyPhoneNumber;
use App\Api\V1\Controllers\OneStepId\UserSubmitsUsername;
use App\Api\V1\Controllers\OneStepId\UserRequestsRegistrationByPhoneNumber;


/**
 * @var Laravel\Lumen\Routing\Router $router
 */
$router->group([
    'prefix' => env('APP_API_VERSION', 'v1'),
], function ($router) {

    /**
     *
     */
    $router->group([
        'prefix' => 'auth',
        'as' => 'users',
        'namespace' => 'OneStepId'
    ], function ($router) {
        
            $router->post('/send-phone', UserRequestsRegistrationByPhoneNumber::class);
            $router->post('/send-username', UserSubmitsUsername::class);
            $router->post('/send-code', VerifyPhoneNumber::class);
    

    });

});
