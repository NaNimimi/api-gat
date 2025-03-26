<?php

namespace App\Repositories\User\Contracts;

use App\Repositories\Contracts\BaseRepositoryContract;

interface UserRepositoryContract extends BaseRepositoryContract
{
    public function findByUsername(string $username);
}
