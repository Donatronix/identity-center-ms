<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    [
        'namespace' => '\App\Api\V1\Controllers',
        'prefix' =>  env('APP_API_PREFIX', ''),
        'as' => 'api.',
        'middleware' => ['auth:api']
    ],
    function ($router) {
        include base_path('app/Api/V1/routes.php');
    }
);
