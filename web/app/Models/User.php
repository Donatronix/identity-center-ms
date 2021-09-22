<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use UuidTrait;
    use SoftDeletes;

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
        'username',
        'phone',
        'email',
        'birthday',
        'password',
        'status'
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
            $model->phone = Str::after($model->phone, '+');
        });
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
}
