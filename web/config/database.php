<?php

return [
    'redis' => [
 
        'client' => env('REDIS_CLIENT', 'phpredis'),
     
      
        'default' => [
            [
                'host' => env('REDIS_HOST', 'localhost'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
        ],

     
    ],
];