<?php
declare(strict_types=1);

namespace Owl\DataMapper\Cache;

trait Apc
{
    use Hooks;

    protected function getCache(array $id): array
    {
        $key = $this->getCacheKey($id);
        $fn = $this->getFn('fetch');

        return $fn($key) ?: [];
    }

    protected function deleteCache(array $id): bool
    {
        $key = $this->getCacheKey($id);
        $fn = $this->getFn('delete');

        return $fn($key);
    }

    protected function saveCache(array $id, array $record, int $ttl = null): bool
    {
        $key = $this->getCacheKey($id);
        $ttl = $ttl ?: $this->getCacheTTL();
        $fn = $this->getFn('store');

        return $fn($key, $record, $ttl);
    }

    private function getFn(string $method): string
    {
        static $prefix;

        if (!$prefix) {
            if (extension_loaded('apcu')) {
                $prefix = 'apcu_';
            } elseif (extension_loaded('apc')) {
                $prefix = 'apc_';
            } else {
                throw new \Exception('Require APC or APCu extension!');
            }
        }

        return $prefix . $method;
    }
}
