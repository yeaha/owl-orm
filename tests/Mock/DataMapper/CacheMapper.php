<?php
namespace Tests\Mock\DataMapper;

class CacheMapper extends Mapper
{
    use Cache\Memory;

    public function hasCached(array $id): bool
    {
        $key = $this->getCacheKey($id);

        return isset(static::$__cache__[$key]);
    }

    public function getCachedData(array $id): array
    {
        $key = $this->getCacheKey($id);

        return static::$__cache__[$key] ?? [];
    }

    public function clearCachedData()
    {
        static::$__cache__ = [];
    }

    protected function getCachePolicy(): array
    {
        return [
            'insert' => true,
            'update' => true,
            'not_found' => true,
        ];
    }
}
