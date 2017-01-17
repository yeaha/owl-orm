<?php
declare(strict_types=1);

namespace Tests\Mock\DataMapper\Cache;

trait Memory
{
    use \Owl\DataMapper\Cache\Hooks;

    protected static $__cache__ = [];

    protected function getCache(array $id): array
    {
        $key = $this->getCacheKey($id);

        return static::$__cache__[$key] ?? [];
    }

    protected function deleteCache(array $id): bool
    {
        $key = $this->getCacheKey($id);

        unset(static::$__cache__[$key]);

        return true;
    }

    protected function saveCache(array $id, array $record, int $ttl = null): bool
    {
        $key = $this->getCacheKey($id);

        static::$__cache__[$key] = $record;

        return true;
    }
}
