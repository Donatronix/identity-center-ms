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
     * Statuses of users
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_BANNED = 2;

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

    /**
     * User Category OR Roles
     *
     */
    const ADMIN_USER = 'Admin';
    const INVESTOR_USER = 'Investor';
    const SUPER_ADMIN_USER = 'Super';

    public static array $types = [
        self::ADMIN_USER,
        self::INVESTOR_USER,
        self::SUPER_ADMIN_USER
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

    /**
     * Rules to validate personal data
     *
     * @param int|null $id
     * @return array
     */
    public static function profileValidationRules(): array
    {
        return [
            'first_name' => 'sometimes|string|min:2|max:60',
            'last_name' => 'sometimes|string|min:2|max:60',

            'username' => 'sometimes|string|unique:users,username',
            'email' => "sometimes|email|unique:users,email",
            'phone' => "sometimes|regex:/\+?\d{7,16}/i|unique:users,phone",

            'birthday' => 'sometimes|nullable|date_format:Y-m-d',
            'subscribed_to_announcement' => 'sometimes|boolean',

            'locale' => 'sometimes|string',

            'address_country' => 'sometimes|string|min:2|max:3',
            'address_line1' => 'sometimes|string|max:150',
            'address_line2' => 'sometimes|nullable|string|max:100',
            'address_city' => 'sometimes|string|max:50',
            'address_zip' => 'sometimes|string|max:15',

//            'address' => 'sometimes|array:country,line1,line2,city,zip',
//            'address.country' => 'sometimes|string|max:3',
//            'address.line1' => 'sometimes|string|max:150',
//            'address.line2' => 'string|max:100',
//            'address.city' => 'sometimes|string|max:50',
//            'address.zip' => 'sometimes|string|max:15'
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
            'birthday' => 'required|date_format:Y-m-d',
            'password' => 'required|min:6|max::32',
            'username' => 'required|string',
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
     * One User has many KYC relation
     *
     * @return HasMany
     */
    public function kycs(): HasMany
    {
        return $this->hasMany(KYC::class);
    }

    public function loginSecurity()
    {
        return $this->hasOne(TwoFactorSecurity::class);
    }

     /**
     * Format user phone number
     * 
     * @param string $phone
     * 
     */
    public static function formatPhoneNum(string $phone)
    {
        // Validate phone number
        $phone_regex = "/^\\+?\\d{1,4}?[-.\\s]?\\(?\\d{1,3}?\\)?[-.\\s]?\\d{1,4}[-.\\s]?\\d{1,4}[-.\\s]?\\d{1,9}$/";

        return preg_match($phone_regex, $phone); // returns 1 if true
    }
}
