<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sumra\SDK\Traits\UuidTrait;

/**
 * KYC Schema
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="KYC",
 *
 *     @OA\Property(
 *         property="id_number",
 *         type="string",
 *         description="National identification number",
 *     ),
 *      @OA\Property(
 *          property="number",
 *          type="integer",
 *          description="Document number",
 *          example="FG1452635"
 *      ),
 *      @OA\Property(
 *          property="country",
 *          type="string",
 *          description="Document country",
 *          example=""
 *      ),
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
 *          example="1"
 *      ),
 *      @OA\Property(
 *          property="file",
 *          type="string",
 *          description="Document file",
 *          example=""
 *      )
 * )
 */
class KYC extends Model
{
    use UuidTrait;

    protected $fillable = [
        'user_id',
        'id_number',
        'document_number',
        'document_country',
        'document_type',
        'document_file',
        'document_back',
        'status'
    ];

    /**
     * Statuses
     *
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public static array $statuses = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED
    ];

    /**
     * Document Types constants
     */
    const DOCUMENT_TYPES_PASSPORT = 1;
    const DOCUMENT_TYPES_ID_CARD = 2;
    const DOCUMENT_TYPES_DRIVERS_LICENSE = 3;
    const DOCUMENT_TYPES_RESIDENCE_PERMIT = 4;

    public static array $document_types = [
        1 => self::DOCUMENT_TYPES_PASSPORT,
        2 => self::DOCUMENT_TYPES_ID_CARD,
        3 => self::DOCUMENT_TYPES_DRIVERS_LICENSE,
        4 => self::DOCUMENT_TYPES_RESIDENCE_PERMIT
    ];

    /**
     * Validation rules
     *
     * @return string[]
     */
    public static function validationRules(): array
    {
        return [
            // 'gender' => 'required|string',
            // 'birthday' => 'required|string',
            'id_number' => 'string|max:100',
            'document_number' => 'string',
            'document_country' => 'string|max:3',
            'document_type' => 'integer|in:1,2,3,4',
            'document_file' => 'required|string'
        ];
    }
}
