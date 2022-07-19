<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Sumra\SDK\Traits\UuidTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Activity Scheme
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="Activity",
 *
 *     @OA\Property(
 *         property="product_id",
 *         type="string",
 *         description="Title of activity",
 *         example="Password Update"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Description of activity",
 *         example="Your password has been updated"
 *     ),
 * 
 * )
 */

class Activity extends Model
{
    use HasFactory;
    use UuidTrait;
    use SoftDeletes;

    /**
     * Mass assignable attributes.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'user_id',
        'description',
        'activity_time'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
