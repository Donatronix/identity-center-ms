<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sumra\SDK\Traits\UuidTrait;

class TwoFactorSecurity extends Model
{
    use UuidTrait;
    protected $fillable = [
        'user_id',
        'secret',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
