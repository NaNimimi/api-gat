<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\User\Contracts\UserRepositoryContract;
use Illuminate\Database\Eloquent\Builder;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class UserRepository
 *
 *
 * @method User create(array $attributes)
 * @method User update(array $attributes, $id)
 * @method User find($id, $columns = ['*'])
 * @method User findOrFail($id, $columns = ['*'])
 * @method User findBy(string $field, $value, array $columns = ['*'])
 */
class UserRepository extends BaseRepository implements UserRepositoryContract
{
    protected $fieldSearchable = [
        'name' => 'like',
        'username' => 'like',
        'roles.name' => 'like',
    ];

    /**
     * @throws RepositoryException
     */
    public function boot(): void
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    protected function getQuery(): Builder
    {
        return $this->model->with(['roles', 'permissions', 'branches']);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return User::class;
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User
    {
        return $this->scopedQuery(function () use ($username) {
            /** @var class-string<User> $modelClass */
            $modelClass = get_class($this->getModel());

            return $modelClass::query()
                ->where('username', $username)
                ->first();
        });
    }
}
