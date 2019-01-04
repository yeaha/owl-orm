<?php

namespace Owl\DataMapper\DB;

/**
 * @method static \Owl\DataMapper\DB\Mapper getMapper()
 */
class Data extends \Owl\DataMapper\Data
{
    protected static $mapper = '\Owl\DataMapper\DB\Mapper';

    /**
     * @return \Owl\DataMapper\DB\Select
     */
    public static function select()
    {
        return static::getMapper()->select();
    }

    /**
     * @return \Owl\DataMapper\DB\Data[]
     */
    public static function getBySQL($sql, array $parameters = [], \Owl\Service $service = null)
    {
        $result = [];

        foreach (static::getBySQLAsIterator($sql, $parameters, $service) as $data) {
            $id = $data->id();

            if (is_array($id)) {
                $result[] = $data;
            } else {
                $result[$id] = $data;
            }
        }

        return $result;
    }

    public static function getBySQLAsIterator($sql, array $parameters = [], \Owl\Service $service = null)
    {
        return static::getMapper()->getBySQLAsIterator($sql, $parameters, $service);
    }
}
