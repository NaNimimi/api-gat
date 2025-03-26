<?php

namespace App\Services\User;

use App\DTO\User\CreateUserData;
use App\DTO\User\UpdateUserData;
use App\Models\User;
use App\Repositories\User\UserRepository;
use App\Services\BaseService;
use App\Services\Traits\UserRolePermissionTrait;
use App\Transformers\Users\UserTransformer;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use League\Fractal\Manager;
use Throwable;

class UserService extends BaseService implements UserServiceInterface
{
    use UserRolePermissionTrait;

    protected string $resourceKey = 'users';

    protected string $entityName = 'User';

    private array $roles = [];

    public function __construct(
        DatabaseManager $databaseManager,
        UserRepository $repository,
        Manager $fractal,
        UserTransformer $defaultTransformer
    ) {
        parent::__construct($databaseManager, $repository, $fractal, $defaultTransformer);
    }

    public function createFromDTO(CreateUserData $dto): array
    {
        $this->roles = $dto->getRoleIds();
        return $this->create($dto->toDatabase());
    }

    public function updateFromDTO(int|string $id, UpdateUserData $dto): array
    {
        $this->roles = $dto->getRoleIds();
        return $this->update($id, $dto->toDatabase());
    }

    /**
     * @throws Throwable
     */
    public function getFully(): array|static
    {
        return $this->withTransformer(new UserTransformer(true))->with(['roles', 'permissions'])->all();
    }


    /**
     * @throws Throwable
     */
    public function getById(int|string $id): static|array
    {
        $this->setTransformer(new UserTransformer(true));
        return $this->find($id);
    }

    protected function prepareData(array $data): array
    {
        return Arr::except($data, ['roles']);
    }

    protected function afterCreate(Model $model): void
    {
        /** @var User $model */
        $this->syncRolesAndLoadRelations($model, $this->roles);
    }

    protected function afterUpdate(Model $model): void
    {
        /** @var User $model */
        $this->syncRolesAndLoadRelations($model, $this->roles);
    }

    private function syncRolesAndLoadRelations(User $user, array $roleIds): void
    {
        if ($roleIds) {
            $user->roles()->sync($roleIds);
        }
        $user->load(['roles', 'permissions']);
    }

    private function getUser(int|string $id): User
    {
        return $this->repository->skipPresenter()->findOrFail($id);
    }
}
