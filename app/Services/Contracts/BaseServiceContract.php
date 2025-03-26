<?php

namespace App\Services\Contracts;

interface BaseServiceContract
{
    public function all();

    public function paginate(?int $perPage = null);

    public function find(int|string $id);

    public function findBy(string $field, mixed $value );

    public function create(array $data);

    public function update(int|string $id, array $data);

    public function getWhere(array $conditions);
}
