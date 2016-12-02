<?php
namespace Tests\Mock\DataMapper;

class CacheMapper extends Mapper
{
    use Cache\Memory;

    public function hasCached(array $id)
    {
        $key = $this->getCacheKey($id);

        return isset(static::$__cache__[$key]);
    }

    public function getCachedData(array $id)
    {
        $key = $this->getCacheKey($id);

        return static::$__cache__[$key] ?? false;
    }

    public function clearCachedData()
    {
        static::$__cache__ = [];
    }

    protected function getCachePolicy()
    {
        return [
            'insert'    => true,
            'update'    => true,
            'not_found' => true,
        ];
    }
}
