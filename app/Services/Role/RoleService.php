<?php

namespace App\Services\Role;

use App\DTO\Role\CreateRoleData;
use App\DTO\Role\UpdateRoleData;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Role\RoleRepository;
use App\Services\BaseService;
use App\Transformers\Roles\RoleTransformer;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use League\Fractal\Manager;
use Throwable;

class RoleService extends BaseService implements RoleServiceInterface
{
    protected string $resourceKey = 'roles';

    protected string $entityName = 'Role';

    private array $permissions = [];

    public function __construct(
        DatabaseManager $databaseManager,
        RoleRepository $repository,
        Manager $fractal,
        RoleTransformer $defaultTransformer
    ) {
        parent::__construct($databaseManager, $repository, $fractal, $defaultTransformer);
    }

    /**
     * @throws Throwable
     */
    public function createFromDTO(CreateRoleData $dto): array
    {
        $this->permissions = $dto->gerPermissionIds();
        return $this->create($dto->toDatabase());
    }

    /**
     * @throws Throwable
     */
    public function updateFromDTO(int|string $id, UpdateRoleData $dto): array
    {
        $this->permissions = $dto->gerPermissionIds();
        return $this->update($id, $dto->toDatabase());
    }

    /**
     * @throws Throwable
     */
    public function getFully(): array|static
    {
        return $this->with( ['permissions'])->all();
    }

    /**
     * @throws Throwable
     */
    public function getById(int|string $id): static|array
    {
        $this->setTransformer(new RoleTransformer(true));
        return $this->find($id);
    }

    protected function prepareData(array $data): array
    {
        return Arr::except($data, ['permissions']);
    }

    protected function afterCreate(Model $model): void
    {
        /** @var Role $model */
        $this->syncRolesAndLoadRelations($model, $this->permissions);
    }

    protected function afterUpdate(Model $model): void
    {
        /** @var Role $model */
        $this->syncRolesAndLoadRelations($model, $this->permissions);
    }

    private function syncRolesAndLoadRelations(Role  $role, array  $permissionIds): void
    {

        $role->syncPermissions($permissionIds);
        foreach ($permissionIds as $permissionId) {
            Permission::find($permissionId)->assignRole($role);
        }


         $role->load(['permissions']);
    }
}
