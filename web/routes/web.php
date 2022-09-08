<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group([
    'prefix' => env('APP_API_PREFIX', '')
], function ($router) {
    include base_path('app/Api/V1/routes.php');
});

/*-------------------------
   T E S T S  Routes
-------------------------- */
$router->group([
    'prefix' => env('APP_API_PREFIX', '') . '/tests'
], function ($router) {
    $router->get('db-test', function () {
        if (DB::connection()->getDatabaseName()) {
            echo "Connected successfully to database: " . DB::connection()->getDatabaseName();
        }
    });


    $router->get('register-user', function () {

//        PubSub::publish('JoinNewUserRequest', [
//            'user_id' => '9736ce26-446f-4add-ab05-f8629961734c',
//            'name' => 'Imabhipatidar',
//            'username' => 'Imabhipatidar',
//            'phone' => '917000421246',
//            'country' => '',
//            'type' => 'client',
//            'application_id' => 'V14567890123',
//            'referral_code' => 'WSL4TD-FHD2F2'
//
//        ], 'Production.ReferralsMS');

    });

});
