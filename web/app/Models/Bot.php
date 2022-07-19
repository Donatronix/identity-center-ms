<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sumra\SDK\Traits\UuidTrait;

/**
 * Class Bot Model
 *
 * @package App\Models
 */
class Bot extends Model
{
    use HasFactory;
    use UuidTrait;
}
