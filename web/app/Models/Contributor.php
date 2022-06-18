<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sumra\SDK\Traits\OwnerTrait;
use Sumra\SDK\Traits\UuidTrait;

/**
 * Contributor Person Scheme
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="ContributorPerson",
 *
 *     @OA\Property(
 *         property="first_name",
 *         type="string",
 *         description="First name",
 *         example="Jhon"
 *     ),
 *     @OA\Property(
 *         property="last_name",
 *         type="string",
 *         description="Last name of contributors",
 *         example="Smith"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         description="Contributor's email",
 *     ),
 *     @OA\Property(
 *         property="address",
 *         type="object",
 *         description="Address of contributor",
 *
 *         @OA\Property(
 *             property="country",
 *             type="string",
 *             description="Country of contributor (ISO 3166-1 alpha-2 format)",
 *             example="GB"
 *         ),
 *         @OA\Property(
 *             property="line1",
 *             type="string",
 *             description="Address line 1",
 *             example="My Big Avenue, 256"
 *         ),
 *         @OA\Property(
 *             property="line2",
 *             type="string",
 *             description="Address Line 2 (optional)",
 *             example=""
 *         ),
 *         @OA\Property(
 *             property="city",
 *             type="string",
 *             description="City of contributor",
 *             example=""
 *         ),
 *         @OA\Property(
 *             property="zip",
 *             type="string",
 *             description="Post / Zip code",
 *             example="05123"
 *         )
 *     )
 * )
 */

/**
 * Contributor Identify Scheme
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="ContributorIdentify",
 *
 *     @OA\Property(
 *         property="id_number",
 *         type="string",
 *         description="National identification number",
 *     ),
 *     @OA\Property(
 *         property="gender",
 *         type="string",
 *         description="Gender of contributor",
 *         enum={"", "m", "f"},
 *         example="m"
 *     ),
 *     @OA\Property(
 *         property="date_birthday",
 *         type="date",
 *         description="Birthday date of contributor",
 *         example="1974-10-25"
 *     ),
 *     @OA\Property(
 *         property="document",
 *         type="object",
 *         description="Document of contributors",
 *
 *         @OA\Property(
 *             property="number",
 *             type="integer",
 *             description="Document number",
 *             example="FG1452635"
 *         ),
 *         @OA\Property(
 *             property="country",
 *             type="string",
 *             description="Document country",
 *             example=""
 *         ),
 *         @OA\Property(
 *             property="type",
 *             type="string",
 *             description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
 *             example="1"
 *         ),
 *         @OA\Property(
 *             property="file",
 *             type="string",
 *             description="Document file",
 *             example=""
 *         )
 *     )
 * )
 */
class Contributor extends Model
{
    use HasFactory;
    use SoftDeletes;
    use UuidTrait;
    use OwnerTrait;

    /**
     * Document Types constants
     */
    const DOCUMENT_TYPES_PASSPORT = 1;
    const DOCUMENT_TYPES_ID_CARD = 2;
    const DOCUMENT_TYPES_DRIVERS_LICENSE = 3;
    const DOCUMENT_TYPES_RESIDENCE_PERMIT = 4;

    /**
     * Contributor statuses constant
     */
    const STATUS_STEP_1 = 1;
    const STATUS_STEP_2 = 2;
    const STATUS_STEP_3 = 3;
    const STATUS_STEP_4 = 4;
    const STATUS_ACTIVE = 5;
    const STATUS_INACTIVE = 6;

    /**
     * Contributor document types array
     *
     * @var int[]
     */
    public static array $document_types = [
        1 => self::DOCUMENT_TYPES_PASSPORT,
        2 => self::DOCUMENT_TYPES_ID_CARD,
        3 => self::DOCUMENT_TYPES_DRIVERS_LICENSE,
        4 => self::DOCUMENT_TYPES_RESIDENCE_PERMIT
    ];

    /**
     * Contributor statuses array
     *
     * @var array|int[]
     */
    public static array $statuses = [
        self::STATUS_STEP_1,
        self::STATUS_STEP_2,
        self::STATUS_STEP_3,
        self::STATUS_STEP_4,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'gender',
        'date_birthday',
        'email',
        'id_number',

        'address_country',
        'address_line1',
        'address_line2',
        'address_city',
        'address_zip',

        'document_number',
        'document_country',
        'document_type',
        'document_file',

        'is_agreement',
        'status'
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
     * @return string[]
     */
    public static function personValidationRules(): array
    {
        return [
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => 'required|string|max:100',
            'address' => 'required|array:country,line1,line2,city,zip',
            'address.country' => 'required|string|max:3',
            'address.line1' => 'required|string|max:150',
            'address.line2' => 'string|max:100',
            'address.city' => 'required|string|max:50',
            'address.zip' => 'required|string|max:15'
        ];
    }

    /**
     * @return string[]
     */
    public static function identifyValidationRules(): array
    {
        return [
            'gender' => 'required|string',
            'date_birthday' => 'required|string',
            'id_number' => 'required|string|max:100',
            'document' => 'required|array:number,country,type,file',
            'document.number' => 'required|string',
            'document.country' => 'required|string|max:3',
            'document.type' => 'required|integer|min:1|max:4',
            'document.file' => 'required|string'
        ];
    }

    /**
     * One Contributor has many Orders relation
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * One Contributor has many Identification relation
     *
     * @return HasMany
     */
    public function identifications(): HasMany
    {
        return $this->hasMany(Identification::class);
    }
}
