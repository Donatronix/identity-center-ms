<?php

namespace App\Models;

use App\Exceptions\InvalidTokenException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class TwoFactorAuth extends Model
{
    protected $fillable = [
        "sid",
        "user_id",
        "code"
    ];

    public static function generateToken()
    {
        do {
            # code...
            $token = Str::random(8);
        } while (self::where("code", $token)->exists());

        return $token;
    }
   
    public static function verifyToken(User $user, $token)
    {
        try {
            return self::where("code", $token)->where('user_id', $user->id)->firstOrFail();
        } catch (ModelNotFoundException $th) {
            throw new InvalidTokenException("Invalid Token");
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
