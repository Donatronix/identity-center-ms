<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\InvalidTokenException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TwoFactorAuth extends Model
{
    //

    protected $fillable = ["sid", "user_id", "code"];

    public function user(){
         
        return $this->belongsTo(User::class, "user_id");
    }

   

    public static function generateToken()
    {

        do {
            # code...
            $token = Str::random(8);
            
        } while (self::where("code",$token)->exists());

        return $token;
        
    }


    public static function verifyToken(User $user, $token)
    {
        
        try {

            $twoFa = self::where("code",$token)->where('user_id', $user->id)->firstOrFail();
            return $twoFa;

        } catch (ModelNotFoundException $th) {
            
            throw new InvalidTokenException("Invalid Token");
        }
    }
}
