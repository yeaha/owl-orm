<?php
namespace Tests\DataMapper\Cache;

class HooksTest extends \PHPUnit_Framework_TestCase
{
    protected $class = '\Tests\Mock\DataMapper\Data';

    protected function setUp()
    {
        ($this->class)::setMapper('\Tests\Mock\DataMapper\CacheMapper');
    }

    protected function tearDown()
    {
        ($this->class)::getMapper()->clearCachedData();
        ($this->class)::setMapper('\Tests\Mock\DataMapper\Mapper');
    }

    protected function setAttributes(array $attributes)
    {
        ($this->class)::getMapper()->setAttributes($attributes);
    }

    public function test()
    {
        $this->setAttributes([
            'id'  => ['type' => 'uuid', 'primary_key' => true],
            'foo' => ['type' => 'string'],
        ]);

        $class  = $this->class;
        $mapper = $class::getMapper();

        // cache "NOT FOUND"
        $id = '5fd55767-1e1d-4c6d-843a-b2a816970300';
        $class::find($id);

        $this->assertTrue($mapper->hasCached(['id' => $id]));
        $this->assertSame(['__IS_NOT_FOUND__' => 1], $mapper->getCachedData(['id' => $id]));

        // cache insert
        $data = new $class([
            'id'  => $id,
            'foo' => 'FOO',
        ]);
        $data->save();
        $this->assertSame(
            [
                'id'  => $id,
                'foo' => 'FOO',
            ],
            $mapper->getCachedData(['id' => $id])
        );

        // cache update
        $data->foo = 'bar';
        $data->save();
        $this->assertSame(
            [
                'id'  => $id,
                'foo' => 'bar',
            ],
            $mapper->getCachedData(['id' => $id])
        );

        // remove cache after data destroy
        $data->destroy();
        $this->assertFalse($mapper->hasCached(['id' => $id]));
    }
}
