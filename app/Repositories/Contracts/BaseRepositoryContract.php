<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

interface BaseRepositoryContract extends RepositoryInterface
{
    /**
     * Retrieve all data of repository
     */
    public function all($columns = ['*']);

    /**
     * Find data by id
     */
    public function find($id, $columns = ['*']);

    /**
     * Find data by field and value
     */
    public function findByField($field, $value, $columns = ['*']);

    /**
     * Find data by multiple fields
     */
    public function findWhere(array $where, $columns = ['*']);

    /**
     * Find data by multiple values in one field
     */
    public function findWhereIn($field, array $values, $columns = ['*']);

    /**
     * Find data by excluding multiple values in one field
     */
    public function findWhereNotIn($field, array $values, $columns = ['*']);

    /**
     * Save a new entity in repository
     */
    public function create(array $attributes);

    /**
     * Update an entity in repository by id
     */
    public function update(array $attributes, $id);

    /**
     * Delete an entity in repository by id
     */
    public function delete($id);

    /**
     * Force delete an entity in repository by id
     */
    public function forceDelete($id);

    /**
     * Order collection by a given column
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * Load relations
     */
    public function with($relations);

    /**
     * Add a basic where clause to the query
     */
    public function where($column, $operator = null, $value = null);

    /**
     * Add a "where in" clause to the query
     */
    public function whereIn($column, $values);

    /**
     * Add a "where not in" clause to the query
     */
    public function whereNotIn($column, $values);

    /**
     * Add a "where null" clause to the query
     */
    public function whereNull($column);

    /**
     * Add a "where not null" clause to the query
     */
    public function whereNotNull($column);

    public function getRepository(): RepositoryInterface;
}
