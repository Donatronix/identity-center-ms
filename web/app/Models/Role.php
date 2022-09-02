<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Roles constant
     */
    const ROLE_SUPER_ADMIN = 'Super Admin';
    const ROLE_ADMIN = 'Admin';
    const ROLE_INVESTOR = 'Investor';
    const ROLE_STAFF = 'Staff';
    const ROLE_INFLUENCER = 'Influencer';

    /**
     * Roles array
     *
     * @var int[]
     */
    public static array $roles = [
        'super_admin' => self::ROLE_SUPER_ADMIN,
        'admin' => self::ROLE_ADMIN,
        'investor' => self::ROLE_INVESTOR,
        'staff' => self::ROLE_STAFF,
        'influencer' => self::ROLE_INFLUENCER
    ];
}
