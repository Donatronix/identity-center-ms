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
        'prefix' => 'users',
        'as' => 'users'
    ], function ($router) {
        $router->group([
            'prefix' => 'one-step',
            'as' => '.one-step'
        ], function ($router) {
            $router->get('/', 'UserOneStepController@index');
            $router->post('/', 'UserOneStepController@store');
            $router->get('/{id}', 'UserOneStepController@show');
            $router->patch('/{id}', 'UserOneStepController@update');
        });
    });

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

    /**
     * ADMIN PANEL
     */
    $router->group([
        'prefix' => 'admin',
        'namespace' => 'Admin'
    ], function ($router) {
        $router->group([
            'prefix' => 'users',
            'as' => 'admin.users'
        ], function ($router) {
            $router->get('/', 'UserController@index');
            $router->get('/{id}', 'UserController@show');
            $router->patch('/{id}', 'UserController@approve');
            $router->delete('/{id}', 'UserController@destroy');
            $router->post('/verify', 'UserController@verify');
            $router->post('/verify/send', 'UserController@verify_email');
        });
    });
});
