<?php

use Sumra\SDK\Helpers\Helper;

return (static function () {
    $settings = [
        /**
         * Authorization Token parameters
         */
        'token_params' => [
            'id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID', ''),
            'secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET', '')
        ],

        /**
         * Default password for user (Useful for Token Grant)
         */
        'password' =>  env('USER_PASSWORD', 'password')
    ];

    return array_merge(Helper::getConfig('settings'), $settings);
})();
