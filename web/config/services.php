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
    ],

    'youtube'=>[
        'client_id'=>env('YOUTUBE_CLIENT_ID'),
        'client_secret'=>env('YOUTUBE_CLIENT_SECRET'),
        'redirect'=>env('YOUTUBE_REDIRECT_URI')
    ],
    
    'tiktok'=>[
        'client_id'=>env('TIKTOK_CLIENT_ID'),
        'client_secret'=>env('TIKTOK_CLIENT_SECRET'),
        'redirect'=>env('TIKTOK_REDIRECT_URI')
    ],
    
    'vkontakte'=>[
        'client_id'=>env('VKONTAKTE_CLIENT_ID'),
        'client_secret'=>env('VKONTAKTE_CLIENT_SECRET'),
        'redirect'=>env('VKONTAKTE_REDIRECT_URI')
    ],
    
    'discord'=>[
        'client_id'=>env('DISCORD_CLIENT_ID'),
        'client_secret'=>env('DISCORD_CLIENT_SECRET'),
        'redirect'=>env('DISCORD_REDIRECT_URI')
    ],
    
    'pinterest'=>[
        'client_id'=>env('PINTEREST_CLIENT_ID'),
        'client_secret'=>env('PINTEREST_CLIENT_SECRET'),
        'redirect'=>env('PINTEREST_REDIRECT_URI')
    ],
    
    'linkedin'=>[
        'client_id'=>env('LINKEDIN_CLIENT_ID'),
        'client_secret'=>env('LINKEDIN_CLIENT_SECRET'),
        'redirect'=>env('LINKEDIN_REDIRECT_URI')
    ],
    
    'snapchat'=>[
        'client_id'=>env('SNAPCHAT_CLIENT_ID'),
        'client_secret'=>env('SNAPCHAT_CLIENT_SECRET'),
        'redirect'=>env('SNAPCHAT_REDIRECT_URI')
    ]
];