<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Sumra\SDK\Traits\UuidTrait;


class RecoveryQuestion extends Model
{
    use HasFactory;
    use UuidTrait;
    
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
