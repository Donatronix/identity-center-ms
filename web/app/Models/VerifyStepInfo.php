<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VerifyStepInfo extends Model
{
    
    protected $table = 'verify_step_infos';
    
    protected $fillable = [
        "username",
        "channel",
        "receiver",
        "code",
        "validity"
    ];

    protected $casts = [
        'validity' => 'datetime',
    ];
    
    /**
     *  Create an One-Time-password (for phone number verification)
     * 
     * @param int $strlength
     * 
     * @return string 
     */
    public static function generateOTP($strlength):string
    {
        return Str::random($strlength);
    }

    /**
     *  Create a One-Time-password validity period
     * 
     * @param integer $minutes
     * @return integer 
     */
    public static function tokenValidity($minutes):int
    {
       return time()+($minute*60*60);
    }

    /**
     *  Validate input data
     * 
     * @return array 
     */
    public static function rules():array
    {
        return [
            'username'=>'required|string',
            'channel'=>'required|string',
            'phone'=>'nullable|string|max:20',
            'handler'=>'nullable|string',
            'messenger'=>'required|string'
        ];
    }
}
