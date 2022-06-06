<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryQuestion extends Model
{
    protected $table = 'verify_step_infos';
    
    protected $fillable = [
        "username",
        "channel",
        "receiver",
        "code",
        "validity"
    ];
}
