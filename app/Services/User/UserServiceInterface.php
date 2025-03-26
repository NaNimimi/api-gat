<?php

namespace App\Services\User;

use App\DTO\User\CreateUserData;
use App\DTO\User\UpdateUserData;
use Throwable;

interface UserServiceInterface
{
    /**
     * Создать пользователя из DTO.
     *
     * @param  CreateUserData  $dto  Данные для создания пользователя
     * @return array Данные созданного пользователя
     *
     * @throws Throwable
     */
    public function createFromDTO(CreateUserData $dto): array;

    /**
     * Обновить пользователя из DTO.
     *
     * @param  int|string  $id  Идентификатор пользователя
     * @param  UpdateRoleData  $dto  Данные для обновления
     * @return array Данные обновленного пользователя
     *
     * @throws Throwable
     */
    public function updateFromDTO(int|string $id, UpdateUserData $dto): array;

    /**
     * Получить роли пользователя.
     *
     * @param  int|string  $id  Идентификатор пользователя
     * @return array Список имен ролей
     *
     * @throws Throwable
     */
    public function getUserRoles(int|string $id): array;

    /**
     * Получить разрешения пользователя.
     *
     * @param  int|string  $id  Идентификатор пользователя
     * @return array Список имен разрешений
     *
     * @throws Throwable
     */
    public function getUserPermissions(int|string $id): array;

    /**
     * Проверить наличие роли у пользователя.
     *
     * @param  int|string  $id  Идентификатор пользователя
     * @param  string  $role  Название роли
     *
     * @throws Throwable
     */
    public function hasRole(int|string $id, string $role): bool;

    /**
     * Проверить наличие разрешения у пользователя.
     *
     * @param  int|string  $id  Идентификатор пользователя
     * @param  string  $permission  Название разрешения
     *
     * @throws Throwable
     */
    public function hasPermission(int|string $id, string $permission): bool;
}
