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
        "namespace" => "\App\Api\V1\Controllers\OneStepId"
    ], function ($router) {
            $router->post('/send-phone/{botID}', "UserRequestsRegistrationByPhoneNumber");
            $router->post('/send-username', "UserSubmitsUsername");
            $router->post('/send-code', "VerifyPhoneNumber");
            $router->post('/send-sms/{botID}', "SendTokenSmsToUser");
    });
});
