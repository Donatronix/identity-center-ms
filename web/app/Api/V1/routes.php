<?php

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
