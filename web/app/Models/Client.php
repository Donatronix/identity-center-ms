<?php

namespace App\Models;

use Webpatser\Uuid\Uuid;
use Laravel\Passport\Client as PassportClient;

class Client extends PassportClient
{
    public $incrementing = false;
    
    public static function boot()
    {
        static::creating(function ($model) {
            $model->uuid = Uuid::generate()->string;
        });
    }
}
