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
            'prefix' => 'user-account/v1',
            "namespace" => "OneStepId1",
        ], function ($router) {
            $router->post('/send-phone', "PhoneVerifyController");
            $router->post('/send-sms', "SendSMSController");
            $router->post('/send-code', "OTPVerifyController");
            $router->post('/send-username', "UsernameSubmitController");
        });

        /**
         * OneStep 2.0
         */
        $router->group([
            'prefix' => 'user-account/v2',
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
        // 'middleware' => [
        //     'checkUser',
        //     'auth:api'
        // ]
    ], function ($router) {
        /**
         * User Profile
        */
        $router->group([
            'prefix' => 'user-profile',
        ], function ($router) {
            $router->post('/', 'UserProfileController@store');
            $router->get('/me', "UserProfileController@show");
            $router->patch('/{id:[a-fA-F0-9\-]{36}}', 'UserProfileController@update');

            $router->put('/update/phone', 'UserProfileController@updatePhone');
            $router->put('/update/password', "UserProfileController@updatePassword");

            $router->post('/update-email', 'UserProfileController@updateMyEmail');
            $router->post('/verify-email-send', 'UserProfileController@verify_email');
            $router->post('/validate-edit-phone', 'UserProfileController@validateEditPhoneNumber');
            $router->post('/validate-edit-email', 'UserProfileController@validateEditEmail');

            /**
             * User Agreement
             */
            $router->patch('/agreement', "AgreementController");
           
        });

        /**
         * Social media connector
        */
        $router->group([
            'prefix' => 'user-profile',
        ], function ($router) {
            $router->post('/redirect', "SocialMediaController@createRedirectUrl");
            $router->get('/{provider}/callback', "SocialMediaController@mediaCallback");
            $router->get('/social/connections', "SocialMediaController@getMediaData");
            $router->get('/whatsapp/connect', "SocialMediaController@whatsappConnect");
        });

        /**
         * User KYC identify
         */
        $router->group([
            'prefix' => 'user-identify',
        ], function ($router) {
            $router->post('/', 'IdentificationController@store');
            $router->post('/start', 'IdentificationController@identifyStart');
        });

        /**
         * Auth - refresh token
         */
        $router->post('/refresh-token', 'AuthController@refresh');

    });

    /**
     * ADMIN PANEL ACCESS
     *
     * Admin / super admin access level (E.g CEO company)
     */
    $router->group([
        'prefix' => 'admin',
        'namespace' => 'Admin',
        // 'middleware' => [
        //     'checkUser',
        //     'checkAdmin'
        // ]
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
            $router->put('/update/{id:[a-fA-F0-9\-]{36}}', 'UserController@update');
            $router->patch('/approve/{id:[a-fA-F0-9\-]{36}}', 'UserController@approve');
            $router->delete('/{id:[a-fA-F0-9\-]{36}}', 'UserController@destroy');
            $router->post('/verify', 'UserController@verify');
            $router->post('/verify/send', 'UserController@verifyEmail');
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
        $router->post('identify/{object}', 'IdentifyWebhookController');
    });
});
