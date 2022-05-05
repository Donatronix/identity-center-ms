<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;
use Sumra\SDK\Traits\UuidTrait;
/**
 * User Schema
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="User",
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
 *         property="phone_number",
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
 *        property="address_country",
 *        type="string",
 *        description="Country code",
 *     ),
 *     @OA\Property(
 *        property="address_line1",
 *        type="string",
 *        description="First line of address. may contain house number, street name, etc.",
 *     ),
 *     @OA\Property(
 *        property="address_line2",
 *        type="string",
 *        description="Second line of address.",
 *     ),
 *     @OA\Property(
 *        property="address_city",
 *        type="string",
 *        description="Name of city",
 *     ),
 *     @OA\Property(
 *        property="address_zip",
 *        type="string",
 *        description="Zip code",
 *     ),
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
    use SoftDeletes;
    use HasFactory;
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
     * @var int[]
     */
    public static array $statuses = [
        self::STATUS_INACTIVE,
        self::STATUS_ACTIVE,
        self::STATUS_BANNED
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
        'phone_number',
        'email',
        'birthday',
        'password',
        'status',

        'subscribed_to_announcement',
        'address_country',
        'address_line1',
        'address_line2',
        'address_city',
        'address_zip',

        'document_number',
        'document_country',
        'document_type',
        'document_file',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
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
            $model->phone_number = Str::after($model->phone_number, '+');
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
     * Rules to validate personal data
     *
     * @param  int|null  $id
     * @return array
     */
    public static function personalValidationRules(?int $id = null): array
    {
        $rules = [
            'first_name' => 'required|string|min:3|max:60',
            'last_name' => 'required|string|min:3|max:60',
            'email' => "sometimes|email|unique:users,email"
                . ($id ? ",{$id}" : ''),
            'phone_number' => "sometimes|regex:/\+?\d{7,16}/i|unique:users,phone_number"
                . ($id ? ",{$id}" : ''),
            'birthday' => 'sometimes|nullable|date_format:d-m-Y',
            'subscribed_to_announcement' => 'sometimes|boolean',
            'address_country' => 'required|string|min:2|max:3',
            'address_line1' => 'required|string|max:150',
            'address_line2' => 'sometimes|nullable|string|max:100',
            'address_city' => 'sometimes|string|max:50',
            'address_zip' => 'required|string|max:10',
        ];

        return $rules;
    }

    
    /**
     * Validation rules for identity verification
     * 
     * @return string[]
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
}
