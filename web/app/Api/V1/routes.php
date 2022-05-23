<?php

/**
 * @var Laravel\Lumen\Routing\Router $router
 */
$router->group([
    'prefix' => env('APP_API_VERSION', ''),
    'namespace' => '\App\Api\V1\Controllers'
], function ($router) {
    /**
     * PUBLIC ACCESS
     */
    $router->group([
        'prefix' => 'auth',
        'as' => 'auth',
        "namespace" => "OneStepId"
    ], function ($router) {
        $router->post('/send-phone/{botID}', "UserRequestsRegistrationByPhoneNumber");
        $router->post('/send-sms/{botID}', "SendTokenSmsToUser");
        $router->post('/send-username', "UserSubmitsUsername");
        $router->post('/send-code', "VerifyPhoneNumber");

        $router->group(['prefix' => 'create'], function($router) {

        });

        $router->group(['prefix' => 'recovery'], function($router) {

        });
    });

    /**
     * PRIVATE ACCESS
     */

    $router->group(['middleware' => 'auth:api'], function ($router) {
        $router->get('users', 'UserController@index');
    });

    $router->group(['middleware' => 'checkUser'], function ($router) {
        $router->group([ 'prefix' => 'users', 'as' => 'users'], function ($router) {
            $router->post('/', 'UserController@store');
            $router->get('/{id}', 'UserController@show');
            $router->patch('/{id}', 'UserController@update');
            $router->post('/validate-edit-phone', 'UserController@validateEditPhoneNumber');
            $router->post('/update-phone', 'UserController@updateMyPhoneNumber');
            $router->post('/identify', 'UserController@identifyStart');
            $router->post('/identify-webhook', 'UserController@identifyWebHook');
        });
    });

    /**
     * ADMIN PANEL
     */
    $router->group([
        'prefix' => 'admin',
        'namespace' => 'Admin',
        'middleware' => [
            'checkUser',
            'checkAdmin'
        ]
    ], function ($router) {
        $router->group([
            'prefix' => 'users',
            'as' => 'admin.users'
        ], function () use ($router) {
            $router->get('/', 'UserController@index');
            $router->post('/', 'UserController@store');
            $router->get('/{id}', 'UserController@show');
            $router->patch('/{id}', 'UserController@approve');
            $router->delete('/{id}', 'UserController@destroy');
            $router->post('/verify', 'UserController@verify');
            $router->post('/verify/send', 'UserController@verify_email');
        });
    });
});
