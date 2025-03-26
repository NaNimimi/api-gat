<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Role\CreateRoleData;
use App\DTO\Role\UpdateRoleData;
use App\Http\Requests\Traits\RequestValidationTrait;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class RoleRequest extends BaseRequest
{
    use RequestValidationTrait;

    private const ROUTE_STORE = 'roles.store';

    private const ROUTE_UPDATE = 'roles.update';

    private const ROUTE_INDEX = 'roles.index';

    private const VALID_SORT_DIRECTIONS = ['asc', 'desc'];

    private const PER_PAGE_MIN = 1;

    private const PER_PAGE_MAX = 100;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $routeName = $this->route()?->getName();

        return match ($routeName) {
            self::ROUTE_STORE => $this->createRules(),
            self::ROUTE_UPDATE => $this->updateRules(),
            self::ROUTE_INDEX => $this->indexRules(),
            default => [],
        };
    }

    /**
     * Get validation rules for creating a user.
     */
    protected function createRules(): array
    {
        return $this->defaultRules();
    }

    /**
     * Get validation rules for updating a user.
     */
    protected function updateRules(): array
    {
        return $this->defaultRules(
            roleId: $this->route('role')
        );
    }

    /**
     * Common validation rules for user creation and update.
     */
    private function defaultRules( $roleId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($roleId)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'integer', 'exists:permissions,id'],
        ];
    }

    /**
     * Get validation rules for listing users.
     */
    protected function indexRules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sortedBy' => ['sometimes', 'string', 'in:'.implode(',', self::VALID_SORT_DIRECTIONS)],
            'per_page' => [
                'sometimes',
                'integer',
                'min:'.self::PER_PAGE_MIN,
                'max:'.self::PER_PAGE_MAX,
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareRoles();
    }

    /**
     * Prepare roles by converting them to integers and filtering non-numeric values.
     */
    private function prepareRoles(): void
    {
        if ($this->has('permissions') && is_array($this->get('permissions'))) {
            $roles = array_filter(
                array_map('intval', (array) $this->get('permissions')),
                static fn ($role) => $role > 0
            );
            $this->merge(['permissions' => $roles]);
        }
    }

    /**
     * Convert validated data to a DTO based on the route.
     *
     * @throws InvalidArgumentException
     */
    public function toDTO(): CreateRoleData|UpdateRoleData
    {
        $data = $this->validated();
        $routeName = $this->route()?->getName();

        return match ($routeName) {
            self::ROUTE_STORE => CreateRoleData::from($data),
            self::ROUTE_UPDATE => UpdateRoleData::from($data),
            default => throw new InvalidArgumentException("Invalid route '$routeName' for DTO conversion"),
        };
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Имя',
            'username' => 'Имя пользователя',
            'permissions' => 'Разрешении',
            'permissions.*' => 'Разрешения',
            'search' => 'Поиск',
            'orderBy' => 'Сортировка по',
            'sortedBy' => 'Направление сортировки',
            'per_page' => 'Количество записей на странице',
            'force' => 'Принудительное удаление',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => ':attribute обязательно для заполнения.',
            'permissions.required' => ':attribute обязательны для заполнения.',
            'permissions.min' => 'Необходимо указать хотя бы одну :attribute.',
            'permissions.*.exists' => 'Указанная :attribute не существует.',
        ];
    }
}
