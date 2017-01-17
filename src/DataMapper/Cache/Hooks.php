<?php
/**
 * @example
 * class MyMapper extends \Owl\DataMapper\DB\Mapper {
 *     use \Owl\DataMapper\Cache\Hooks;
 *
 *     protected function getCache($id) {
 *         // ...
 *     }
 *
 *     protected function deleteCache($id) {
 *         // ...
 *     }
 *
 *     protected function saveCache($id, array $record) {
 *         // ...
 *     }
 * }
 *
 * class MyData extends \Owl\DataMapper\Data {
 *     static protected $mapper = 'MyMapper';
 *
 *     static protected $mapper_options = [
 *         'service' => 'my.db',
 *         'collection' => 'tablename',
 *         'cache_policy' => [
 *             'insert' => false,       // create cache after insert, default disable
 *             'update' => false,       // update cache after update, default disable
 *             'not_found' => false,    // create "not found" cache, default disable, set true or false or integer
 *         ],
 *     ];
 *
 *     static protected $attributes = [
 *         // ...
 *     ];
 * }
 */
declare(strict_types=1);

namespace Owl\DataMapper\Cache;

use Owl\DataMapper\Data;
use Owl\Service;

trait Hooks
{
    /**
     * @param mixed $id
     *
     * @return array
     */
    abstract protected function getCache(array $id): array;

    /**
     * @param mixed $id
     *
     * @return bool
     */
    abstract protected function deleteCache(array $id): bool;

    /**
     * @param mixed $id
     * @param array $record
     * @param int   $ttl
     *
     * @return bool
     */
    abstract protected function saveCache(array $id, array $record, int $ttl = null): bool;

    /**
     * create cache after save new data, if cache policy set.
     *
     * @param \Owl\DataMapper\Data $data
     */
    protected function __afterInsert(Data $data)
    {
        $policy = $this->getCachePolicy();
        $id = $data->id(true);

        if ($policy['insert']) {
            $record = $this->unpack($data);
            $record = $this->removeNullValues($record);

            $this->saveCache($id, $record);
        } elseif ($policy['not_found']) {
            $this->deleteCache($id);
        }

        parent::__afterInsert($data);
    }

    /**
     * delete or update cache after save data.
     *
     * @param \Owl\DataMapper\Data $data
     */
    protected function __afterUpdate(Data $data)
    {
        $policy = $this->getCachePolicy();
        $id = $data->id(true);

        if ($policy['update']) {
            $record = $this->unpack($data);
            $record = $this->removeNullValues($record);

            $this->saveCache($id, $record);
        } else {
            $this->deleteCache($id);
        }

        parent::__afterUpdate($data);
    }

    /**
     * delete cache after delete data.
     *
     * @param \Owl\DataMapper\Data $data
     */
    protected function __afterDelete(Data $data)
    {
        $this->deleteCache($data->id(true));

        parent::__afterDelete($data);
    }

    /**
     * delete cache before refresh data.
     *
     * @param \Owl\DataMapper\Data $data
     *
     * @return \Owl\DataMapper\Data
     */
    public function refresh(Data $data): Data
    {
        $this->deleteCache($data->id(true));

        return parent::refresh($data);
    }

    /**
     * 获得缓存策略配置.
     */
    protected function getCachePolicy(): array
    {
        $defaults = [
            'insert' => false,
            'update' => false,
            'not_found' => false,
        ];

        if (!$this->hasOption('cache_policy')) {
            return $defaults;
        }

        $policy = $this->getOption('cache_policy');

        if (is_array($policy)) {
            return array_merge($defaults, $policy);
        }

        if (DEBUG) {
            throw new \Exception('Invalid cache policy setting');
        }

        return $defaults;
    }

    /**
     * return record from cache if cache is created, or save data into cache.
     *
     * @param array        $id
     * @param \Owl\Service $service
     * @param string       $collection
     *
     * @return array
     */
    protected function doFind(array $id, Service $service = null, string $collection = null): array
    {
        if ($record = $this->getCache($id)) {
            return isset($record['__IS_NOT_FOUND__']) ? false : $record;
        }

        if ($record = parent::doFind($id, $service, $collection)) {
            $record = $this->removeNullValues($record);
            $this->saveCache($id, $record);
        } else {
            $policy = $this->getCachePolicy();

            if ($ttl = $policy['not_found']) {
                $ttl = is_numeric($ttl) ? (int) $ttl : null;
                $this->saveCache($id, ['__IS_NOT_FOUND__' => 1], $ttl);
            }
        }

        return $record;
    }

    /**
     * @param string $key
     *
     * @return object
     */
    protected function getCacheService(string $key): Service
    {
        $service_name = $this->getOption('cache_service');

        return Service\get($service_name, $key);
    }

    /**
     * @param array $id
     *
     * @return string
     */
    protected function getCacheKey(array $id): string
    {
        $prefix = $this->hasOption('cache_key')
        ? $this->getOption('cache_key')
        : sprintf('entity:%s', str_replace('\\', ':', trim($this->class, '\\')));

        $prefix = $prefix . '@';

        if ($this->hasOption('cache_key_prefix')) {
            $prefix = $this->getOption('cache_key_prefix') . ':' . $prefix;
        }

        ksort($id);

        $kv = [];
        foreach ($id as $k => $v) {
            $kv[] = sprintf('%s:%s', $k, $v);
        }

        $key = $prefix . implode(':', $kv);

        return strtolower($key);
    }

    /**
     * @return int
     */
    protected function getCacheTTL(): int
    {
        return $this->hasOption('cache_ttl') ? $this->getOption('cache_ttl') : 300;
    }

    /**
     * remove NULL value from record.
     *
     * @param array $record
     *
     * @return array
     */
    private function removeNullValues(array $record): array
    {
        // 值为NULL的字段不用缓存
        foreach ($record as $key => $val) {
            if ($val === null) {
                unset($record[$key]);
            }
        }

        return $record;
    }
}
