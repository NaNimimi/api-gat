<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidTokenException;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Атрибуты, которые должны быть приведены к определенным типам.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'device_type',
        'ip_address',
        'last_used_at',
        'expires_at',
    ];

    /**
     * Правила валидации для атрибутов токена.
     *
     * @var array<string, string>
     */
    private static array $validationRules = [
        'name' => 'required|string|max:255',
        'token' => 'required|string|size:64',
        'device_type' => 'nullable|string|in:mobile,desktop,tablet',
        'ip_address' => 'nullable|ip',
        'expires_at' => 'nullable|date',
    ];

    /**
     * Получить отношение к модели, к которой привязан токен.
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo('tokenable');
    }

    /**
     * Создать новый персональный токен доступа.
     *
     * @param  User  $user  Пользователь, для которого создается токен
     * @param  array<string, mixed>  $details  Дополнительные детали токена
     * @return NewAccessToken Созданный токен
     *
     * @throws InvalidTokenException В случае ошибки при создании токена
     */
    public static function createToken(User $user, array $details = []): NewAccessToken
    {
        try {
            // Подготовка данных для создания токена
            $tokenData = array_merge([
                'device_type' => $details['device_type'] ?? null,
                'ip_address' => request()->ip(),
                'expires_at' => $details['expires_at'] ?? null,
            ], $details);

            // Создание токена через Sanctum
            $token = $user->createToken(
                $tokenData['name'] ?? 'default',
                $tokenData['abilities'] ?? ['*'],
                $tokenData['expires_at']
            );

            // Обновление модели токена дополнительными данными
            $token->accessToken->forceFill($tokenData)->save();

            // Логирование успешного создания токена
            Log::info('Токен успешно создан', [
                'user_id' => $user->id,
                'device_type' => $token->accessToken->device_type,
            ]);

            return $token;
        } catch (Exception $e) {
            // Логирование ошибки при создании токена
            Log::error('Ошибка при создании токена', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new InvalidTokenException("Не удалось создать токен: {$e->getMessage()}");
        }
    }

    /**
     * Проверить, истек ли срок действия токена.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Проверить, активен ли токен.
     *
     * Токен считается активным, если он не истек и использовался в последние 7 дней.
     */
    public function isActive(): bool
    {
        return ! $this->isExpired() &&
            $this->last_used_at !== null &&
            $this->last_used_at->gt(now()->subDays(7));
    }

    /**
     * Записать использование токена.
     *
     * Обновляет поле last_used_at текущей датой и временем.
     */
    public function recordUsage(): void
    {
        $this->forceFill([
            'last_used_at' => now(),
        ])->save();
    }

    /**
     * Получить активные токены для определенного типа устройства.
     *
     * @param  string|null  $deviceType  Тип устройства (если null, возвращаются все активные токены)
     * @return Collection<int, static> Коллекция активных токенов
     */
    public static function getActiveTokens(?string $deviceType = null): Collection
    {
        $query = static::query()
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('last_used_at', '>', now()->subDays(7));

        if ($deviceType !== null) {
            $query->where('device_type', $deviceType);
        }

        return $query->get();
    }

    /**
     * Удалить истекшие токены.
     *
     * Удаляет токены, срок действия которых истек, или которые не использовались более 30 дней.
     *
     * @return int Количество удаленных токенов
     */
    public static function pruneExpiredTokens(): int
    {
        return static::query()
            ->where(function (Builder $query) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            })
            ->orWhere('last_used_at', '<', now()->subDays(30))
            ->delete();
    }

    /**
     * Проверить, принадлежит ли токен к определенному типу устройства.
     *
     * @param  string  $type  Тип устройства
     */
    public function isFromDeviceType(string $type): bool
    {
        return $this->device_type === $type;
    }

    /**
     * Валидировать данные токена.
     *
     * @throws ValidationException Если данные токена не прошли валидацию
     */
    protected function validateToken(): void
    {
        $validator = Validator::make(
            $this->attributes,
            self::$validationRules
        );

        $validator->validate();
    }

    /**
     * Загрузка модели.
     *
     * Добавляет хук для валидации токена при его создании.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $token) {
            $token->validateToken();
        });
    }

    /**
     * Фильтр запросов по IP-адресу.
     *
     * @param  Builder  $query  Построитель запросов
     * @param  string  $ip  IP-адрес для фильтрации
     */
    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Фильтр запросов по типу устройства.
     *
     * @param  Builder  $query  Построитель запросов
     * @param  string  $deviceType  Тип устройства для фильтрации
     */
    public function scopeForDevice(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Получить дату и время последней активности токена.
     */
    public function getLastActivity(): ?Carbon
    {
        return $this->last_used_at;
    }

    /**
     * Установить дату истечения срока действия токена.
     *
     * @param  Carbon|null  $expiresAt  Дата истечения срока действия
     */
    public function setExpiration(?Carbon $expiresAt): self
    {
        $this->expires_at = $expiresAt;
        $this->save();

        return $this;
    }
}
