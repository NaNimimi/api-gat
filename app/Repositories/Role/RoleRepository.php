<?php

namespace App\Repositories\Role;

use App\Models\Role;
use App\Repositories\BaseRepository;
use App\Repositories\Role\Contracts\RoleRepositoryContract;

/**
 * Class RoleRepository
 *
 *
 * @method Role create(array $attributes)
 * @method Role update(array $attributes, $id)
 * @method Role find($id, $columns = ['*'])
 * @method Role findOrFail($id, $columns = ['*'])
 * @method Role findBy(string $field, $value, array $columns = ['*'])
 */
class RoleRepository extends BaseRepository implements RoleRepositoryContract
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Role::class;
    }
}
