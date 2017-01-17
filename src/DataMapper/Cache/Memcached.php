<?php
declare(strict_types=1);

namespace Owl\DataMapper\Cache;

trait Memcached
{
    use Hooks;

    protected function getCache(array $id): array
    {
        $key = $this->getCacheKey($id);
        $memcached = $this->getCacheService($key);

        try {
            if ($record = $memcached->get($key)) {
                $record = \Owl\safe_json_decode($record, true);
            }

            return $record ?: [];
        } catch (\UnexpectedValueException $exception) {
            if (DEBUG) {
                throw $exception;
            }

            return [];
        }
    }

    protected function deleteCache(array $id): bool
    {
        $key = $this->getCacheKey($id);
        $memcached = $this->getCacheService($key);

        return $memcached->delete($key);
    }

    protected function saveCache(array $id, array $record, int $ttl = null): bool
    {
        $key = $this->getCacheKey($id);
        $memcached = $this->getCacheService($key);
        $ttl = $ttl ?: $this->getCacheTTL();

        return $memcached->set($key, \Owl\safe_json_encode($record, JSON_UNESCAPED_UNICODE), $ttl);
    }
}
