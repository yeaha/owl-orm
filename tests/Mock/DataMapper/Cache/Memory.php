<?php
namespace Tests\Mock\DataMapper\Cache;

trait Memory
{
    use \Owl\DataMapper\Cache\Hooks;

    protected static $__cache__ = [];

    protected function getCache(array $id)
    {
        $key = $this->getCacheKey($id);

        return static::$__cache__[$key] ?? [];
    }

    protected function deleteCache(array $id)
    {
        $key = $this->getCacheKey($id);

        unset(static::$__cache__[$key]);
    }

    protected function saveCache(array $id, array $record, $ttl = null)
    {
        $key = $this->getCacheKey($id);

        static::$__cache__[$key] = $record;

        // var_dump(static::$__cache__);
    }
}
