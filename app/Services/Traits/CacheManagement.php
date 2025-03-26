<?php

namespace App\Services\Traits;

use Illuminate\Support\Facades\Cache;
use JsonException;

trait CacheManagement
{
    protected bool $cacheEnabled = false;

    protected int $defaultCacheTtl = 60; // В минутах

    protected ?int $requestCacheTtl = null;

    protected function getCacheTag(): string
    {
        return 'service:'.class_basename($this);
    }

    /**
     * @throws JsonException
     */
    protected function getCacheKey(string $method, ...$args): string
    {
        $queryKey = md5(json_encode([
            'method' => $method,
            'with' => $this->with,
            'select' => $this->select,
            'scope' => $this->repository->getCriteria(), // Учитываем условия scopeQuery
            'args' => $args,
        ], JSON_THROW_ON_ERROR));
        return "service:$this->resourceKey:$queryKey";
    }

    public function enableCache(?int $ttl = null): static
    {
        $this->requestCacheTtl = $ttl ?? $this->defaultCacheTtl;

        return $this;
    }

    public function setCache(bool $enabled = true, int $ttl = 60): static
    {
        $this->cacheEnabled = $enabled;
        $this->defaultCacheTtl = $ttl;

        return $this;
    }

    protected function applyCache(callable $operation, string $key): mixed
    {
        $ttl = $this->requestCacheTtl ?? ($this->cacheEnabled ? $this->defaultCacheTtl : null);
        $result = $ttl ? Cache::tags($this->getCacheTag())->remember($key, $ttl * 60, $operation) : $operation();

        $this->requestCacheTtl = null; // Сбрасываем TTL для конкретного запроса

        return $result;
    }

    protected function invalidateCache(): void
    {
        Cache::tags($this->getCacheTag())->flush();
    }
}
