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
     *
     * level with free access to the endpoint
     */
    $router->group([
        'namespace' => 'Public'
    ], function ($router) {
        /**
         * OneStep 1.0
         */
        $router->group([
            'prefix' => 'auth',
            'as' => 'auth',
            "namespace" => "OneStepId1",
        ], function ($router) {
            $router->post('/send-phone', "PhoneVerifyController");
            $router->post('/send-sms', "SendSMSController");
            $router->post('/send-code', "OTPVerifyController");
            $router->post('/send-username', "UsernameSubmitController");

            $router->post('/refresh-token', 'AuthController@refresh');
        });

        /**
         * OneStep 2.0
         */
        $router->group([
            'prefix' => 'user-account',
            "namespace" => "OneStepId2"
        ], function ($router) {
            $router->post('/create', "CreateUserIDController@createAccount");
            $router->post('/otp/resend', "CreateUserIDController@resendOTP");
            $router->post('/otp/verify', "CreateUserIDController@verifyOTP");
            $router->post('/update', "CreateUserIDController@updateUser");
            $router->post('/update/recovery', "CreateUserIDController@updateRecoveryQuestion");

            /**
             * Recovery user account
             */
            $router->group([
                'prefix' => 'recovery',
            ], function ($router) {
                $router->post('/userinfo', "UserInfoRecoveryController@recoveryInfo");
                $router->post('/otp/verify', "UserInfoRecoveryController@verifyOTP");
                $router->post('/questions', "UserInfoRecoveryController@recoveryQuestions");
                $router->post('/sendid', "UserInfoRecoveryController@sendRecoveredID");
            });
        });
    });

    /**
     * USER APPLICATION PRIVATE ACCESS
     *
     * Application level for users
     */
    $router->group([
        'namespace' => 'Application',
        'middleware' => [
            'checkUser',
            'auth:api'
        ]
    ], function ($router) {
        /**
         * CREATE USER ACCOUNT
        */
        $router->group([
            'prefix' => 'user-profile',
            "namespace" => "User"
        ], function ($router) {
            $router->get('/me', "UserProfileController@show");
            $router->put('/password/change', "UserProfileController@updatePassword");
            $router->put('/username/update', "UserProfileController@updateUsername");
            $router->put('/fullname/update', "UserProfileController@updateFullname");
            $router->put('/country/update', "UserProfileController@updateCountry");
            $router->put('/email/update', "UserProfileController@updateEmail");
            $router->put('/locale/update', "UserProfileController@updateLocal");
        });

        /**
         * USER SOCIAL MEDIA CONNECTIONS
        */
        $router->group([
            'prefix' => 'user-profile',
            "namespace" => "OneStepId2"
        ], function ($router) {
            $router->post('/redirect', "SocialMediaController@createRedirectUrl");
            $router->get('/{provider}/callback', "SocialMediaController@mediaCallback");
            $router->get('/social/connections', "SocialMediaController@getMediaData");
            $router->get('/whatsapp/connect', "SocialMediaController@whatsappConnect");
        });

        $router->get('users', 'UserController@index');


        $router->group([
            'prefix' => 'users',
            'as' => 'users'
        ], function ($router) {
            $router->post('/', 'UserController@store');
            $router->get('/{id}', 'UserController@show');
            $router->patch('/{id}', 'UserController@update');
            $router->post('/validate-edit-phone', 'UserController@validateEditPhoneNumber');
            $router->post('/update-phone', 'UserController@updateMyPhoneNumber');
            $router->post('/identify', 'UserController@identifyStart');
        });

        /**
         * Contributor
         */
        $router->group([
            'prefix' => 'users',
        ], function ($router) {
            $router->get('/', 'UserController@show');
            $router->post('/', 'UserController@store');
            $router->post('/identify', 'UserController@identifyStart');
            $router->put('/identify', 'UserController@update');
            $router->patch('/agreement', 'UserController@agreement');
        });
    });

    /**
     * ADMIN PANEL ACCESS
     *
     * Admin / super admin access level (E.g CEO company)
     */
    $router->group([
        'prefix' => 'admin',
        'namespace' => 'Admin',
        'middleware' => [
            'checkUser',
            'checkAdmin'
        ]
    ], function ($router) {
        /**
         * Users
         */
        $router->group([
            'prefix' => 'users',
            'as' => 'admin.users',
        ], function () use ($router) {
            $router->get('/', 'UserController@index');
            $router->post('/', 'UserController@store');
            $router->get('/{id:[a-fA-F0-9\-]{36}}', 'UserController@show');
            $router->delete('/{id:[a-fA-F0-9\-]{36}}', 'UserController@destroy');

            $router->get('/', 'UserController@index');
            $router->post('/', 'UserController@store');
            $router->get('/{id:[a-fA-F0-9\-]{36}}', 'UserController@show');
            $router->put('/{id:[a-fA-F0-9\-]{36}}', 'UserController@update');
            $router->patch('/{id:[a-fA-F0-9\-]{36}}', 'UserController@approve');
            $router->delete('/{id:[a-fA-F0-9\-]{36}}', 'UserController@destroy');
            $router->post('/verify', 'UserController@verify');
            $router->post('/verify/send', 'UserController@verify_email');
        });

        /**
         * Add Admins to microservice
         */
        $router->group([
            'prefix' => 'service/admins',
            'as' => 'admin.administrators',
        ], function () use ($router) {
            $router->post('/', 'ServiceAdminController@store');
            $router->patch('/', 'ServiceAdminController@update');
            $router->delete('/', 'ServiceAdminController@destroy');
        });
    });

    /**
     * WEBHOOKS
     *
     * Access level of external / internal software services
     */
    $router->group([
        'prefix' => 'webhooks',
        'namespace' => 'Webhooks'
    ], function ($router) {
        $router->post('/identify-webhook', 'UserController@identifyWebHook');

        $router->post('identify/{type}', 'IdentifyWebhookController');
//        $router->post('identify/events', 'IdentifyWebhookController@webhookEvents');
//        $router->post('identify/notifications', 'IdentifyWebhookController@webhookNotifications');
    });
});
