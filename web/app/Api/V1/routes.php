<?php

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
        'prefix' => 'user',
        'as' => 'users',
        'namespace' => 'OneStepId'
    ], function ($router) {
        $router->group([
            'prefix' => 'one-step',
            'as' => '.one-step'
        ], function ($router) {
            $router->post('/register-request', UserRequestsRegistrationByPhoneNumber::class);
    
        });
    });

    /**
     * ADMIN PANEL
     */
    $router->group(
        [
            'prefix' => 'admin',
            'namespace' => 'Admin'
        ],
        function ($router) {
            $router->group([
                'prefix' => 'users',
                'as' => 'admin.users'
            ], function () use ($router) {
                $router->get('/', 'UserController@index');
                $router->get('/{id}', 'UserController@show');
                $router->patch('/{id}', 'UserController@approve');
                $router->delete('/{id}', 'UserController@destroy');
                $router->post('/verify', 'UserController@verify');
                $router->post('/verify/send', 'UserController@verify_email');
            });
        }
    );
});
