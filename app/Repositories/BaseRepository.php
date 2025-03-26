<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryContract;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository as PrettusRepository;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * @method Model|Builder getModel()
 * @method mixed scopedQuery(Closure $param)
 */
abstract class BaseRepository extends PrettusRepository implements BaseRepositoryContract
{
    public function boot(): void
    {
        try {
            $this->pushCriteria(app(RequestCriteria::class));
        } catch (RepositoryException) {

        }
    }

    public function getRepository(): RepositoryInterface
    {
        return $this;
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model()), true);
    }

    public function findBy(string $field, $value, array $columns = ['*'])
    {
        return $this->findByField($field, $value, $columns)->first();
    }

    protected function withSoftDelete(callable $callback): mixed
    {
        return $this->usesSoftDeletes() ? $callback() : false;
    }

    public function where($column, $operator = null, $value = null)
    {
        return $this->scopedQuery(fn () => $this->getModel()->where($column, $operator ?? '=', $value ?? $operator));
    }

    public function allWithTrashed(array $columns = ['*'])
    {
        return $this->scopedQuery(fn () => $this->getModel()->withTrashed()->get($columns));
    }

    public function findWithTrashed($id, array $columns = ['*'])
    {
        return $this->scopedQuery(fn () => $this->getModel()->withTrashed()->findOrFail($id, $columns));
    }

    public function restore($id)
    {
        return $this->withSoftDelete(fn () => $this->getModel()->withTrashed()->findOrFail($id)?->restore());
    }

    public function forceDelete($id)
    {
        return $this->getModel()->withTrashed()->findOrFail($id)?->forceDelete();
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->getModel()->updateOrCreate($attributes, $values);
    }

    public function whereIn($column, $values)
    {
        return $this->scopedQuery(function () use ($column, $values) {
            /** @var class-string<Model> $modelClass */
            $modelClass = get_class($this->getModel());

            return $modelClass::query()->whereIn($column, $values)->get();
        });
    }

    public function whereNotIn($column, $values)
    {
        return $this->scopedQuery(function () use ($column, $values) {
            /** @var class-string<Model> $modelClass */
            $modelClass = get_class($this->getModel());

            return $modelClass::query()->whereNotIn($column, $values)->get();
        });
    }

    public function whereNull($column)
    {
        return $this->scopedQuery(function () use ($column) {
            /** @var class-string<Model> $modelClass */
            $modelClass = get_class($this->getModel());

            return $modelClass::query()->whereNull($column)->get();
        });
    }

    public function whereNotNull($column)
    {
        return $this->scopedQuery(function () use ($column) {
            /** @var class-string<Model> $modelClass */
            $modelClass = get_class($this->getModel());

            return $modelClass::query()->whereNotNull($column)->get();
        });
    }
}
