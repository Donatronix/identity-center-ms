<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaConnect extends Model
{
    protected $table = 'media_connects';

    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id',
        'media_id',
        'provider',
        'name',
        'email',
        'phone'
    ];

    /**
     *  Validate input data
     * 
     * @return array 
    */
    public static function rules():array
    {
        return [
            'user_id'=>'required|string',
            'media_id'=>'required|string',
            'provider'=>'required|string',
            'name'=>'required|string',
            'email'=>'required|string|email',
            'phone'=>'required|string'
        ];
    }

    protected $timestamp = true;
}
