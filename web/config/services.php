<?php

return [
    'facebook'=>[
        'client_id'=>env('FACEBOOK_CLIENT_ID'),
        'client_secret'=>env('FACEBOOK_CLIENT_SECRET'),
        'redirect'=>env('FACEBOOK_REDIRECT_URI')
    ],

    'instagram'=>[
        'client_id'=>env('INSTAGRAM_CLIENT_ID'),
        'client_secret'=>env('INSTAGRAM_CLIENT_SECRET'),
        'redirect'=>env('INSTAGRAM_REDIRECT_URI')
    ],

    'twitter'=>[
        'client_id'=>env('TWITTER_CLIENT_ID'),
        'client_secret'=>env('TWITTER_CLIENT_SECRET'),
        'redirect'=>env('TWITTER_REDIRECT_URI')
    ],

    'telegram'=>[
        'bot'=>env('TELEGRAM_BOT_NAME'),
        'client_id'=>null,
        'client_secret'=>env('TELEGRAM_TOKEN'),
        'redirect'=>env('TELEGRAM_REDIRECT_URI')
    ]
];