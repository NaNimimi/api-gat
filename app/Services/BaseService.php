<?php

namespace App\Services;

use AllowDynamicProperties;
use App\Services\Contracts\BaseServiceContract;
use App\Services\Traits\CacheManagement;
use App\Services\Traits\DataTransformation;
use App\Services\Traits\ErrorHandling;
use App\Services\Traits\Validation;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\TransformerAbstract;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Validator\Exceptions\ValidatorException;
use Throwable;

#[AllowDynamicProperties]
abstract class BaseService implements BaseServiceContract
{
    use CacheManagement, DataTransformation, ErrorHandling, Validation;

    protected string $resourceKey;
    protected string $singularResourceKey;
    protected string $entityName;
    protected TransformerAbstract $defaultTransformer;

    protected array $with = [];
    protected array $select = [];

    public function __construct(
        protected readonly DatabaseManager $databaseManager,
        protected readonly BaseRepository $repository,
        protected readonly Manager $fractal,
        TransformerAbstract $transformer
    ) {
        $this->resourceKey = $this->determineResourceKey();
        $this->singularResourceKey = Str::singular($this->resourceKey);
        $this->entityName = $this->determineEntityName();
        $this->defaultTransformer = $transformer;
        $this->transformer = $transformer;
    }

    public function with(array $relations): self
    {
        $this->with = array_merge($this->with, $relations);
        return $this;
    }

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function withTransformer(TransformerAbstract $transformer): self
    {
        $this->transformer = $transformer;
        return $this;
    }

    public function setTransformer(TransformerAbstract $transformer): void
    {
        $this->transformer = $transformer;
    }

    /**
     * Fetch all records.
     *
     * @throws Throwable
     */
    public function all(): static|array
    {
        if ($this->isResultPending()) {
            $result = $this->handle(
                fn () => $this->transformCollection(
                    $this->applyCache(
                        fn () => $this->applyQueryOptions($this->repository)->all(),
                        $this->getCacheKey(__FUNCTION__, $this->with, $this->select)
                    )
                ),
                'Error fetching all records'
            );
            $this->resetState();
            return $result;
        }
        return $this;
    }

    /**
     * Fetch paginated records.
     *
     * @throws Throwable
     */
    public function paginate(?int $perPage = 15): static|array
    {
        if ($this->isResultPending()) {
            $result = $this->handle(
                function () use ($perPage) {
                    $paginator = $this->applyCache(
                        fn () => $this->applyQueryOptions($this->repository)->paginate($perPage),
                        $this->getCacheKey(__FUNCTION__, $perPage, $this->with, $this->select)
                    );
                    return $this->transformPaginated(
                        $paginator,
                        [
                            'total' => $paginator->total(),
                            'per_page' => $paginator->perPage(),
                            'current_page' => $paginator->currentPage(),
                            'last_page' => $paginator->lastPage(),
                        ]
                    );
                },
                'Error paginating records'
            );
            $this->resetState();
            return $result;
        }
        return $this;
    }

    /**
     * Find a record by ID.
     *
     * @throws Throwable
     */
    public function find(int|string $id): static|array
    {
        $this->validateId($id);
        if ($this->isResultPending()) {
            $result = $this->handle(
                fn () => $this->transformItem(
                    $this->applyCache(
                        fn () => $this->applyQueryOptions($this->repository)->findOrFail($id),
                        $this->getCacheKey(__FUNCTION__, $id, $this->with, $this->select)
                    )
                ),
                "Error finding record with ID: $id"
            );
            $this->resetState();
            return $result;
        }
        return $this;
    }

    /**
     * Fetch records by conditions.
     *
     * @throws Throwable
     */
    public function getWhere(array $conditions): static|array
    {
        if ($this->isResultPending()) {
            $result = $this->handle(
                fn () => $this->transformCollection(
                    $this->applyCache(
                        fn () => $this->applyQueryOptions($this->repository)->findWhere($conditions),
                        $this->getCacheKey(__FUNCTION__, $conditions, $this->with, $this->select)
                    )
                ),
                'Error fetching records with conditions',
                ['conditions' => $conditions]
            );
            $this->resetState();
            return $result;
        }
        return $this;
    }

    /**
     * Fetch records by a specific field value.
     *
     * @throws Throwable
     */
    public function findBy(string $field, mixed $value): static|array
    {
        if ($this->isResultPending()) {
            $result = $this->handle(
                fn () => $this->transformCollection(
                    $this->applyCache(
                        fn () => $this->applyQueryOptions($this->repository)->findByField($field, $value),
                        $this->getCacheKey(__FUNCTION__, $field, $this->with, $this->select)
                    )
                ),
                'Error fetching records with conditions',
                ['field' => $field]
            );
            $this->resetState();
            return $result;
        }
        return $this;
    }

    /**
     * Create a new record.
     *
     * @throws Throwable
     */
    public function create(array $data, ?callable $afterCreate = null): array
    {
        $this->validateData($data);
        return $this->transaction(fn () => $this->handle(
            fn () => $this->processCreate($this->prepareData($data), $afterCreate),
            "Error creating $this->entityName record",
            ['data' => $this->sanitizeData($data)]
        ));
    }

    /**
     * Update an existing record.
     *
     * @throws Throwable
     */
    public function update(int|string $id, array $data, ?callable $afterUpdate = null): array
    {
        $this->validateId($id);
        $this->validateData($data);
        return $this->transaction(fn () => $this->handle(
            fn () => $this->processUpdate($id, $this->prepareData($data), $afterUpdate),
            "Error updating record with ID: $id",
            ['data' => $this->sanitizeData($data)]
        ));
    }

    public function where(string $field, $value, string $operator = '=', string $boolean = 'and'): self
    {
        $this->repository->scopeQuery(fn ($query) => $query->where($field, $operator, $value, $boolean));
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->repository->scopeQuery(fn ($query) => $query->orderBy($field, $direction));
        return $this;
    }

    /**
     * Fetch the first record.
     *
     * @throws Throwable
     */
    public function first(): static|array|null
    {
        if ($this->isResultPending()) {
            $result = $this->handle(
                function () {
                    $item = $this->applyCache(
                        fn () => $this->applyQueryOptions($this->repository)->first(),
                        $this->getCacheKey(__FUNCTION__, $this->with, $this->select)
                    );
                    return $item ? $this->transformItem($item) : null;
                },
                'Error fetching first record'
            );
            $this->resetState();
            return $result;
        }
        return $this;
    }

    /**
     * Process creation of a record.
     *
     * @throws ValidatorException
     */
    protected function processCreate(array $data, ?callable $afterCreate): array
    {
        $model = $this->repository->create($data);
        if ($afterCreate) {
            $afterCreate($model);
        }
        $this->afterCreate($model);
        return $this->transformItem($model);
    }

    /**
     * Process update of a record.
     *
     * @throws ValidatorException
     */
    protected function processUpdate(int|string $id, array $data, ?callable $afterUpdate): array
    {
        $model = $this->repository->update($data, $id);
        if ($afterUpdate) {
            $afterUpdate($model);
        }
        $this->afterUpdate($model);
        return $this->transformItem($model);
    }

    protected function afterCreate(Model $model): void {}
    protected function afterUpdate(Model $model): void {}

    /**
     * Delete a record.
     *
     * @throws Throwable
     */
    public function delete(int|string $id): bool
    {
        $this->validateId($id);
        return $this->transaction(fn () => $this->handle(
            fn () => $this->repository->skipPresenter()->delete($id),
            "Error deleting record with ID: $id"
        ));
    }

    /**
     * Force delete a record.
     *
     * @throws Throwable
     */
    public function forceDelete(int|string $id): bool
    {
        $this->validateId($id);
        return $this->transaction(fn () => $this->handle(
            fn () => $this->repository->skipPresenter()->forceDelete($id),
            "Error force deleting record with ID: $id"
        ));
    }

    /**
     * Apply query options to the repository.
     */
    protected function applyQueryOptions(BaseRepository $repository): BaseRepository
    {
        $repository = $repository->skipPresenter()->with($this->with);
        if (!empty($this->select)) {
            $repository->scopeQuery(fn ($query) => $query->select($this->select));
        }
        return $repository;
    }

    protected function resetState(): void
    {
        $this->transformer = $this->defaultTransformer;
        $this->with = [];
        $this->select = [];
        $this->repository->resetScope();
    }
    protected function determineResourceKey(): string
    {
        return Str::plural(Str::snake(class_basename($this->repository->getModel())));
    }

    protected function determineEntityName(): string
    {
        return Str::singular(Str::title(Str::snake(class_basename($this->repository->getModel()), ' ')));
    }

    protected function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * Execute a transaction.
     *
     * @throws Throwable
     */
    protected function transaction(callable $callback): mixed
    {
        $this->databaseManager->beginTransaction();
        try {
            $result = $callback();
            $this->databaseManager->commit();
            $this->invalidateCache();
            return $result;
        } catch (Throwable $e) {
            $this->databaseManager->rollBack();
            throw $e;
        }
    }

    protected function isResultPending(): bool
    {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] !== 'enableCache';
    }
}
