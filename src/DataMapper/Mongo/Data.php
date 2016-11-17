<?php
namespace Owl\DataMapper\Mongo;

/**
 * @method static \Owl\DataMapper\Mongo\Mapper getMapper()
 */
class Data extends \Owl\DataMapper\Data
{
    protected static $mapper = '\Owl\DataMapper\Mongo\Mapper';

    public static function query($expr)
    {
        return static::getMapper()->query($expr);
    }

    public static function iterator($expr = null)
    {
        return static::getMapper()->iterator($expr);
    }
}
