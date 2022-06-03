<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * @return integer 
     */
    public static function generateOTP():int
    {
        $timstamp = date("Gis");
        $randome = sprintf("%04d", rand(0,9999));

        return $randome.$timstamp;
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
    public static function roles():array
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
