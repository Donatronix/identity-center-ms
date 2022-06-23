<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryQuestion extends Model
{
    protected $table = 'recovery_questions';
    
    protected $fillable = [
        "user_id",
        "answer_one",
        "answer_two",
        "answer_three"
    ];
    
    /**
     *  Validate input data
     * 
     * @return array 
    */
    public static function rules():array
    {
        return [
            'answer1'=>'required|string',
            'answer2'=>'required|string',
            'answer3'=>'required|string'
        ];
    }
}
