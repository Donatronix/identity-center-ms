<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Sumra\SDK\Traits\UuidTrait;

/**
 * User Profile Scheme
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="UserProfile",
 *
 *     @OA\Property(
 *         property="first_name",
 *         type="string",
 *         description="First name of the user",
 *         example="Jhon"
 *     ),
 *     @OA\Property(
 *         property="last_name",
 *         type="string",
 *         description="Last name of the user",
 *         example="Smith"
 *     ),
 *     @OA\Property(
 *         property="username",
 *         type="string",
 *         description="User's username",
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         description="User's email",
 *     ),
 *     @OA\Property(
 *         property="phone",
 *         type="string",
 *         description="Phone number of user",
 *     ),
 *     @OA\Property(
 *         property="birthday",
 *         type="date",
 *         description="Birthday date of user",
 *         example="1974-10-25"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="number",
 *         description="Status code",
 *         enum={0, 1, 2},
 *     ),
 *     @OA\Property(
 *         property="subscribed_to_announcement",
 *         type="string",
 *         description="Subscription to announcement",
 *         enum={0, 1},
 *     ),
 *     @OA\Property(
 *         property="address",
 *         type="object",
 *         description="Address of user",
 *     ),
 *     @OA\Property(
 *        property="address_country",
 *        type="string",
 *        description="Country code  (ISO 3166-1 alpha-2 format)",
 *        example="GB"
 *     ),
 *     @OA\Property(
 *        property="address_line1",
 *        type="string",
 *        description="First line of address. may contain house number, street name, etc.",
 *        example="My Big Avenue, 256"
 *     ),
 *     @OA\Property(
 *        property="address_line2",
 *        type="string",
 *        description="Second line of address (optional)",
 *        example=""
 *     ),
 *     @OA\Property(
 *        property="address_city",
 *        type="string",
 *        description="Name of city",
 *        example=""
 *     ),
 *     @OA\Property(
 *        property="address_zip",
 *        type="string",
 *        description="Post / Zip code",
 *        example="05123"
 *     )
 * )
 */

/**
 * User Identity Schema
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="UserIdentify",
 *
 *     @OA\Property(
 *         property="id_number",
 *         type="string",
 *         description="National identification number",
 *     ),
 *     @OA\Property(
 *         property="gender",
 *         type="string",
 *         description="Gender of user",
 *         enum={"", "m", "f"},
 *         example="m"
 *     ),
 *     @OA\Property(
 *         property="birthday",
 *         type="date",
 *         description="Birthday date of user",
 *         example="1974-10-25"
 *     ),
 *     @OA\Property(
 *         property="document",
 *         type="object",
 *         description="Document of users",
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
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens;
    use Authenticatable;
    use Authorizable;
    use HasFactory;
    use HasRoles;
    use SoftDeletes;
    use UuidTrait;

    /**
     * Document Types constants
     */
    const DOCUMENT_TYPES_PASSPORT = 1;
    const DOCUMENT_TYPES_ID_CARD = 2;
    const DOCUMENT_TYPES_DRIVERS_LICENSE = 3;
    const DOCUMENT_TYPES_RESIDENCE_PERMIT = 4;

    /**
     * Statuses of users
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_BANNED = 2;

//    /**
//     * User statuses constant
//     */
//    const STATUS_STEP_1 = 1;
//    const STATUS_STEP_2 = 2;
//    const STATUS_STEP_3 = 3;
//    const STATUS_STEP_4 = 4;
//    const STATUS_ACTIVE = 5;
//    const STATUS_INACTIVE = 6;

    /**
     * User document types array
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
     * Array statuses of users
     *
     * @var array|int[]
     */
    public static array $statuses = [
        self::STATUS_INACTIVE,
        self::STATUS_ACTIVE,
        self::STATUS_BANNED
    ];

//    public static array $statuses = [
//        self::STATUS_STEP_1,
//        self::STATUS_STEP_2,
//        self::STATUS_STEP_3,
//        self::STATUS_STEP_4,
//        self::STATUS_ACTIVE,
//        self::STATUS_INACTIVE,
//    ];

    /**
     * User Category OR Roles
     *
     */
    const ADMIN_USER = 'Admin';
    const SUPER_USER = 'Super';
    const STAFF_USER = 'Staff';
    const CLIENT_USER = 'Client';
    const INVESTOR_USER = 'Investor';

    public static array $types = [
        self::ADMIN_USER,
        self::SUPER_USER,
        self::STAFF_USER,
        self::CLIENT_USER,
        self::INVESTOR_USER
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'display_name'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'username',
        'phone',
        'email',
        'birthday',
        'password',
        'access_code',

        'address_country',
        'address_line1',
        'address_line2',
        'address_city',
        'address_zip',

        'id_number',
        'document_number',
        'document_country',
        'document_type',
        'document_file',

        'subscribed_to_announcement',
        'is_agreement',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->model)
                $model->phone = Str::after($model->phone, '+');
        });
    }

    public static function getBySid($sid)
    {
        try {
            $twoFa = TwoFactorAuth::where("sid", $sid)->firstOrFail();
            $user = $twoFa->user;

            return $user;
            //code...
        } catch (ModelNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Rules to validate personal data
     *
     * @param int|null $id
     * @return array
     */
    public static function profileValidationRules(?int $id = null): array
    {
        return [
            'first_name' => 'sometimes|string|min:2|max:60',
            'last_name' => 'sometimes|string|min:2|max:60',

            'username' => 'required|string',
            'email' => "sometimes|email|unique:users,email" . ($id ? ",{$id}" : ''),
            'email' => 'required|string|email',
            'phone' => "sometimes|regex:/\+?\d{7,16}/i|unique:users,phone" . ($id ? ",{$id}" : ''),
            'birthday' => 'sometimes|nullable|date_format:d-m-Y',
            'subscribed_to_announcement' => 'sometimes|boolean',

            'locale' => 'sometimes|string',


            'address_country' => 'required|string|min:2|max:3',
            'address_line1' => 'required|string|max:150',
            'address_line2' => 'sometimes|nullable|string|max:100',
            'address_city' => 'sometimes|string|max:50',
            'address_zip' => 'required|string|max:10'
        ];
    }

    /**
     * @return array
     */
    public static function personValidationRules2(): array
    {
        return [

            'address' => 'required|array:country,line1,line2,city,zip',
            'address.country' => 'required|string|max:3',
            'address.line1' => 'required|string|max:150',
            'address.line2' => 'string|max:100',
            'address.city' => 'required|string|max:50',
            'address.zip' => 'required|string|max:15'
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
     * Validation rules for admin new user
     *
     * @return array
     */
    public static function adminValidationRules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'gender' => 'required|string',
            'birthday' => 'required|string',
            'password' => 'required|min:6|max::32',
            'username' => 'required|string',
            'birthday' => 'required|date_format:Y-m-d',
            'accept_terms' => 'required|boolean',
            'address_country' => 'required|string|max:3',
            'address_line1' => 'required|string|max:150',
            'address_line2' => 'string|max:100',
            'address_city' => 'required|string|max:50',
            'address_zip' => 'required|string|max:15'
        ];
    }

    /**
     * Validation rules for admin new user
     *
     * @return array
     */
    public static function adminUpdateValidateRules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'gender' => 'required|string',
            'username' => 'required|string',
            'birthday' => 'required|date_format:Y-m-d',
            'address_country' => 'required|string|max:3',
            'address_line1' => 'required|string|max:150',
            'address_line2' => 'string|max:100',
            'address_city' => 'required|string|max:50',
            'address_zip' => 'required|string|max:15'
        ];
    }

     /**
     * Validation rules for admin new user
     *
     * @return array
     */
    public static function userValidationRules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            'username' => 'required|string',
            'birthday' => 'required|date_format:Y-m-d',
            'address_country' => 'required|string|max:3',
            'address_line1' => 'required|string|max:150',
            'address_line2' => 'string|max:100',
            'address_zip' => 'required|string|max:15'
        ];
    }

    /**
     * Provide input data validation array.
     *
     * @return array
     */
    public static function rules(): array
    {
        return [
            'username' => 'required|string',
            'fullname' => 'required|string',
            'country' => 'required|string',
            'address' => 'required|string',
            'birthday' => 'required|string'
        ];
    }

    /**
     * Make display_name attribute
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $displayName = sprintf(
            "%s %s",
            $this->first_name,
            $this->last_name
        );
        $displayName = trim(Str::replace('  ', ' ', $displayName));

        if (empty($displayName)) {
            $displayName = $this->username ?? '';
        }

        return $this->attributes['display_name'] = $displayName;
    }

    /**
     * Find the user instance for the given username.
     *
     * @param string $username
     * @return User
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * One User has many Identification relation
     *
     * @return HasMany
     */
    public function identifications(): HasMany
    {
        return $this->hasMany(Identification::class);
    }

    public function loginSecurity()
    {
        return $this->hasOne(TwoFactorSecurity::class);
    }
}
