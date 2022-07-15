<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sumra\SDK\Traits\UuidTrait;

/**
 * KYC Schema
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="UserKYC",
 *     required={"document_type", "document_front", "portrait"},
 *
 *     @OA\Property(
 *         property="id_number",
 *         type="string",
 *         description="National identification number",
 *         example="xxxxxxxxxxxx"
 *     ),
 *     @OA\Property(
 *         property="document_number",
 *         type="string",
 *         description="Document number",
 *         example="FG1452635"
 *     ),
 *     @OA\Property(
 *         property="document_country",
 *         type="string",
 *         description="Document country",
 *         example="UK"
 *     ),
 *     @OA\Property(
 *         property="document_type",
 *         type="string",
 *         description="Type of the Document uploaded (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="document_front",
 *         type="string",
 *         description="Uploaded document front view in base64",
 *         example=""
 *     ),
 *     @OA\Property(
 *         property="document_back",
 *         type="string",
 *         description="Uploaded document back view in base64",
 *         example=""
 *     ),
 *     @OA\Property(
 *         property="portrait",
 *         type="string",
 *         description="Uploaded selfie in base64",
 *         example=""
 *     ),
 * )
 */
class KYC extends Model
{
    use HasFactory;
    use SoftDeletes;
    use UuidTrait;

    /**
     * Statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Document Types constants
     */
    const DOCUMENT_TYPES_PASSPORT = 1;
    const DOCUMENT_TYPES_ID_CARD = 2;
    const DOCUMENT_TYPES_DRIVERS_LICENSE = 3;
    const DOCUMENT_TYPES_RESIDENCE_PERMIT = 4;

    /**
     * @var array|string[]
     */
    public static array $statuses = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED
    ];

    /**
     * @var array|int[]
     */
    public static array $document_types = [
        1 => self::DOCUMENT_TYPES_PASSPORT,
        2 => self::DOCUMENT_TYPES_ID_CARD,
        3 => self::DOCUMENT_TYPES_DRIVERS_LICENSE,
        4 => self::DOCUMENT_TYPES_RESIDENCE_PERMIT
    ];

    /**
     * Mass assignable attributes.
     *
     * @var string[]
     */
    protected $fillable = [
        'id_number',
        'document_number',
        'document_country',
        'document_type',
        'document_front',
        'document_back',
        'portrait',
        'status',
        'user_id',
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
     * Validation rules
     *
     * @return string[]
     */
    public static function validationRules(): array
    {
        return [
            'id_number' => 'string|max:100',
            'document_number' => 'string',
            'document_country' => 'string|max:3',
            'document_type' => 'required|integer|in:1,2,3,4',
            'document_front' => 'required|string',
            'document_back' => 'string',
            'portrait' => 'required|string'
        ];
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
