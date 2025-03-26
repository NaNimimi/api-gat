<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasPermissions;

class Role extends SpatieRole
{
    use HasPermissions,SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
    ];


    protected $with = ['permissions'];

    protected static function booted(): void
    {

    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(static function ($role) {
            /**
             * @var Role $role
             */
            $role->guard_name = 'api';
        });
    }

    protected $attributes = [
        'guard_name' => 'api',
    ];
}
