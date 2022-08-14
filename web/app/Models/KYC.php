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
 *     required={"id_doctype", "address_verify_doctype", "portrait"},
 *
 *     @OA\Property(
 *         property="id_doctype",
 *         type="integer",
 *         description="Type of the Document uploaded (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="address_verify_doctype",
 *         type="integer",
 *         description="Type of the Document uploaded (1 = UTILITY_BILL, 2 = BANK_STATEMENT, 3 = TENANCY_AGREEMENT)",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="id_document",
 *         type="string",
 *         description="Uploaded document front view in base64",
 *         example=""
 *     ),
 *     @OA\Property(
 *         property="address_verify_document",
 *         type="string",
 *         description="Uploaded document back view in base64",
 *         example=""
 *     ),
 *     @OA\Property(
 *         property="portrait",
 *         type="string",
 *         description="Uploaded selfie in base64",
 *         example=""
 *     )
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
    const STATUS_PENDING = 'PENDING';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';

    /**
     * Document Types constants
     */
    const DOCUMENT_TYPES_PASSPORT = 'Passport';
    const DOCUMENT_TYPES_ID_CARD = 'ID Card';
    const DOCUMENT_TYPES_DRIVERS_LICENSE = 'Drivers License';
    const DOCUMENT_TYPES_RESIDENCE_PERMIT = 'Residence Permit';

    /**
     * Address Verification Document Types constants
     */
    const DOCUMENT_TYPES_UTILITY_BILL = 'Utility Bill';
    const DOCUMENT_TYPES_BANK_STATEMENT = 'Bank Statement';
    const DOCUMENT_TYPES_TENANCY_AGREEMENT = 'Tenancy Agreement';

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
     * @var array|int[]
     */
    public static array $verify_document_types = [
        1 => self::DOCUMENT_TYPES_UTILITY_BILL,
        2 => self::DOCUMENT_TYPES_BANK_STATEMENT,
        3 => self::DOCUMENT_TYPES_TENANCY_AGREEMENT
    ];

    /**
     * Mass assignable attributes.
     *
     * @var string[]
     */
    protected $fillable = [
        'id_doctype',
        'address_verify_doctype',
        'id_document',
        'address_verify_document',
        'portrait',
        'status',
        'user_id'
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
            'id_doctype' => 'required|integer|in:1,2,3,4',
            'address_verify_doctype' => 'required|integer|in:1,2,3,4',
            'id_document' => 'required|string',
            'address_verify_document' => 'required|string',
            'portrait' => 'required|string'
        ];
    }

    
    /**
     * Validation rules for identity verification
     *
     * @return array
     */
    public static function identifyValidationRules(): array
    {
        return [
            'gender' => 'required|string',
            'birthday' => 'required|string',
            'id_number' => 'required|string|max:100',
            'document' => 'required|array:number,country,type,file',
            'document.number' => 'required|string',
            'document.country' => 'required|string|max:3',
            'document.type' => 'required|integer|min:1|max:4',
            'document.file' => 'required|string'
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
