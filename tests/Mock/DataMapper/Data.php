<?php
namespace Tests\Mock\DataMapper;

class Data extends \Owl\DataMapper\Data
{
    protected static $mapper = '\Tests\Mock\DataMapper\Mapper';

    protected static $mapper_options = [
        'service'    => 'mock.storage',
        'collection' => 'mock.data',
    ];

    protected static $attributes = [
        'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
    ];

    public static function setMapper(string $mapper_class)
    {
        static::$mapper = $mapper_class;
    }
}
