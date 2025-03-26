<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $table = 'permissions';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'translate',
        'guard_name',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(static function ($role) {
            /**
             * @var Permission $role
             */
            $role->guard_name = 'api';
        });
    }
}
