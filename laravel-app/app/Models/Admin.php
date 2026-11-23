<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_IT_STAFF = 'it_staff';

    public const ROLE_GUEST = 'guest';

    public const MANAGED_ROLES = [
        self::ROLE_IT_STAFF,
        self::ROLE_GUEST,
    ];

    protected $table = 'admin';

    protected $fillable = [
        'username',
        'password_hash',
        'role',
        'is_active',
    ];

    protected $attributes = [
        'role' => self::ROLE_ADMIN,
        'is_active' => true,
    ];

    protected $hidden = [
        'password_hash',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }
}
