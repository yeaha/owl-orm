<?php
namespace Tests\DataMapper;

class DataTest extends \PHPUnit_Framework_TestCase
{
    protected $class = '\Tests\Mock\DataMapper\Data';

    protected function setAttributes(array $attributes)
    {
        $class = $this->class;
        $class::getMapper()->setAttributes($attributes);
    }

    protected function newData(array $values = [], array $options = [])
    {
        $class = $this->class;

        return new $class($values, $options);
    }

    public function testConstruct()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'default' => 'foo'],
            'bar' => ['type' => 'string', 'default' => 'bar', 'allow_null' => true],
        ]);

        $data = $this->newData();

        $this->assertTrue($data->isFresh());
        $this->assertTrue($data->isDirty());
        $this->assertEquals($data->foo, 'foo');
        $this->assertNull($data->bar);

        $data = $this->newData([
            'bar' => 'bar',
        ]);

        $this->assertEquals($data->bar, 'bar');

        $data = $this->newData([], ['fresh' => false]);

        $this->assertFalse($data->isFresh());
        $this->assertFalse($data->isDirty());
        $this->assertEquals('foo', $data->foo);
    }

    public function testClone()
    {
        $class  = $this->class;
        $mapper = $class::getMapper();

        $this->setAttributes([
            'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
        ]);

        $data = $mapper->pack(['id' => 1]);

        $this->assertFalse($data->isFresh());

        $new_data = clone $data;

        $this->assertTrue($new_data->isFresh());
        $this->assertNull($new_data->id());

        //////////////////////////////////////////////////////////////////////////
        $this->setAttributes([
            'id' => ['type' => 'uuid', 'primary_key' => true],
        ]);

        $data = $mapper->pack([
            'id' => '5c376c3a-53bf-4c26-8974-2ac9dc0f4b29',
        ]);
        $new_data = clone $data;

        $this->assertFalse($data->id() === $new_data->id());
        $this->assertRegexp('/^[0-9a-f\-]{36}$/', $new_data->id());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /primary key/
     */
    public function testBadConstruct()
    {
        $this->setAttributes([]);
    }

    public function testSetStrict()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'strict' => true],
        ]);

        $data = $this->newData();

        $data->merge(['foo' => 'foo']);
        $this->assertFalse($data->isDirty('foo'));

        $data->set('foo', 'foo', ['strict' => false]);
        $this->assertFalse($data->isDirty('foo'));

        $data->set('foo', 'foo', ['strict' => true]);
        $this->assertTrue($data->isDirty('foo'));

        $data->foo = 'bar';
        $this->assertEquals($data->foo, 'bar');
    }

    public function testSetRefuseUpdate()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'refuse_update' => true],
        ]);

        $data      = $this->newData();
        $data->foo = 'foo';

        $this->assertEquals($data->foo, 'foo');

        $data = $this->newData(['foo' => 'foo'], ['fresh' => false]);

        // test force set
        $data->set('foo', 'bar', ['force' => true]);
        $this->assertEquals($data->foo, 'bar');

        $this->setExpectedExceptionRegExp('\Exception', '/refuse update/');
        $data->foo = 'foo';
    }

    public function testSetSame()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'allow_null' => true],
            'bar' => ['type' => 'string'],
        ]);

        $data = $this->newData(['bar' => 'bar'], ['fresh' => false]);

        $this->assertFalse($data->isDirty());

        $data->foo = null;
        $this->assertFalse($data->isDirty('foo'));

        $data->bar = 'bar';
        $this->assertFalse($data->isDirty('bar'));
    }

    public function testSetUndefined()
    {
        $this->setAttributes([
            'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
        ]);

        $data = $this->newData();

        $data->set('bar', 'bar', ['strict' => false]);
        $data->merge(['bar' => 'bar']);

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UndefinedPropertyException');
        $data->bar = 'bar';
    }

    public function testGetUndefined()
    {
        $this->setAttributes([
            'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
        ]);

        $data = $this->newData();

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UndefinedPropertyException');
        $data->foo;
    }

    public function testGetObjectValue()
    {
        $this->setAttributes([
            'id'   => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'time' => ['type' => 'datetime', 'default' => 'now'],
        ]);

        $data = $this->newData();
        $this->assertNotSame($data->time, $data->time);
    }

    public function testPick()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'protected' => true],
            'bar' => ['type' => 'string'],
        ]);

        $data = $this->newData([
            'foo' => 'foo',
            'bar' => 'bar',
        ], ['fresh' => false]);

        $values = $data->pick();

        $this->assertFalse(array_key_exists('id', $values));
        $this->assertFalse(array_key_exists('foo', $values));
        $this->assertTrue(array_key_exists('bar', $values));

        $values = $data->pick('foo', 'bar', 'baz');

        $this->assertTrue(array_key_exists('foo', $values));
        $this->assertTrue(array_key_exists('bar', $values));
        $this->assertFalse(array_key_exists('baz', $values));
    }

    public function testGetID()
    {
        $this->setAttributes([
            'foo' => ['type' => 'string', 'primary_key' => true],
        ]);

        $data = $this->newData(['foo' => 'foo']);
        $this->assertEquals($data->id(), 'foo');
        $this->assertSame($data->id(true), ['foo' => 'foo']);

        $this->setAttributes([
            'foo' => ['type' => 'string', 'primary_key' => true],
            'bar' => ['type' => 'string', 'primary_key' => true],
        ]);

        $data = $this->newData(['foo' => 'foo', 'bar' => 'bar']);
        $this->assertSame($data->id(), ['foo' => 'foo', 'bar' => 'bar']);
    }

    public function testGetOptions()
    {
        $foo_options = \Tests\Mock\DataMapper\FooData::getOptions();

        $this->assertEquals($foo_options['service'], 'foo.service');
        $this->assertEquals($foo_options['collection'], 'foo.collection');
        $this->assertEquals(count($foo_options['attributes']), 2);
        $this->assertArrayHasKey('readonly', $foo_options);
        $this->assertArrayHasKey('strict', $foo_options);

        $bar_options = \Tests\Mock\DataMapper\BarData::getOptions();

        $this->assertEquals($bar_options['service'], 'bar.service');
        $this->assertEquals($bar_options['collection'], 'bar.collection');
        $this->assertEquals(count($bar_options['attributes']), 3);
    }

    public function testDeprecatedPrimaryKey()
    {
        $this->setExpectedExceptionRegExp('\RuntimeException', '/primary key/');

        $this->setAttributes([
            'id' => ['type' => 'string', 'primary_key' => true, 'deprecated' => true],
        ]);
    }

    public function testDeprecatedAttribute()
    {
        $this->setAttributes([
            'id'  => ['type' => 'string', 'primary_key' => true],
            'bar' => ['type' => 'string', 'deprecated' => true],
        ]);

        $class  = $this->class;
        $mapper = $class::getMapper();

        $attributes = $mapper->getAttributes();
        $this->assertArrayNotHasKey('bar', $attributes);

        $this->assertFalse($mapper->hasAttribute('bar'));

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\DeprecatedPropertyException');
        $data = $this->newData();
        $bar  = $data->bar;
    }

    public function testSetIn()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true],
            'doc' => ['type' => 'json'],
            'msg' => ['type' => 'string'],
        ]);

        $data = $this->newData(['id' => 1], ['fresh' => false]);

        $this->assertFalse($data->isDirty('doc'));

        $data->setIn('doc', 'foo', 1);
        $this->assertSame(['foo' => 1], $data->get('doc'));
        $this->assertTrue($data->isDirty('doc'));

        $data->setIn('doc', 'bar', 2);
        $this->assertSame(['foo' => 1, 'bar' => 2], $data->get('doc'));

        try {
            $data->setIn('msg', 'foo', 1);
            $this->fail('test setIn failed');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        return $data;
    }

    /**
     * @depends testSetIn
     */
    public function testGetIn($data)
    {
        $this->assertSame(1, $data->getIn('doc', 'foo'));
        $this->assertSame(2, $data->getIn('doc', 'bar'));

        $this->assertFalse($data->getIn('doc', 'foobar'));
        $this->assertFalse($data->getIn('doc', ['foo', 'bar']));
    }

    public function testPushIn()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true],
            'doc' => ['type' => 'json'],
        ]);

        $data = $this->newData();

        $data->pushIn('doc', 'a', 1);
        $this->assertSame(['a' => [1]], $data->doc);

        $data->pushIn('doc', 'a', 2);
        $this->assertSame(['a' => [1, 2]], $data->doc);

        $data->unsetIn('doc', 'a');

        $data->pushIn('doc', ['a', 'b'], 1);
        $this->assertSame(['a' => ['b' => [1]]], $data->doc);

        $data->pushIn('doc', ['a', 'b'], 2);
        $this->assertSame(['a' => ['b' => [1, 2]]], $data->doc);
    }

    public function testValidateAttributeAllowNull()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string'],
            'bar' => ['type' => 'string', 'allow_null' => true],
        ]);

        $data = $this->newData();

        try {
            $data->validate();
            $this->fail('validate "allow_null" falied');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        $data->foo = '';
        try {
            $data->validate();
            $this->fail('validate "allow_null" falied');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        $data->foo = null;
        try {
            $data->validate();
            $this->fail('validate "allow_null" falied');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        $data->foo = 'foo';
        $this->assertTrue($data->validate());

        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'doc' => ['type' => 'json'],
        ]);

        $data = $this->newData();

        try {
            $data->validate();
            $this->fail('validate "allow_null" falied');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }
    }

    public function testValidateAttributeRegexp()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'regexp' => '/^a.+z$/'],
        ]);

        $data      = $this->newData();
        $data->foo = 'abc';

        try {
            $data->validate();
            $this->fail('validate "regexp" failed');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        $data->foo = 'abz';
        $this->assertTrue($data->validate());
    }

    public function testValidateComplexTypeProperty()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'doc' => [
                'type'       => 'json',
                'allow_null' => true,
                'schema'     => [
                    'a' => ['type' => 'integer'],
                    'b' => ['type' => 'integer', 'required' => false],
                    'c' => [
                        'type' => 'array',
                        'keys' => [
                            'd' => ['type' => 'string', 'enum_eq' => ['foo', 'bar']],
                            'e' => ['type' => 'string', 'regexp' => '/^a.+z$/'],
                        ],
                    ],
                ],
            ],
        ]);

        $data = $this->newData();
        $this->assertTrue($data->validate());

        $data->setIn('doc', 'b', 1);
        try {
            $data->validate();
            $this->fail('validate complex failed');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        $data->setIn('doc', 'a', 1);
        $data->setIn('doc', ['c', 'd'], 'foo');
        $data->setIn('doc', ['c', 'e'], 'aaaaaaz');
        $this->assertTrue($data->validate());

        $data->setIn('doc', ['c', 'd'], 'baz');
        try {
            $data->validate();
            $this->fail('validate complex type failed');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        $data->setIn('doc', ['c', 'd'], 'bar');
        $this->assertTrue($data->validate());
    }

    public function testGetterSetter()
    {
        $this->setAttributes([
            'id'      => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo'     => ['type' => 'string'],
            'foo_bar' => ['type' => 'string'],
        ]);

        $data = $this->newData();

        $data->setFoo('bar');
        $this->assertTrue($data->isDirty('foo'));
        $this->assertEquals('bar', $data->foo);
        $this->assertEquals('bar', $data->getFoo());

        $data->setFoobar('baz');
        $this->assertTrue($data->isDirty('foo_bar'));
        $this->assertEquals('baz', $data->getFoobar());

        try {
            $data->setBar(1);
            $this->fail('set undefined property using setter');
        } catch (\Owl\DataMapper\Exception\UndefinedPropertyException $ex) {
        }

        try {
            $data->getBar();
            $this->fail('get undefined property using setter');
        } catch (\Owl\DataMapper\Exception\UndefinedPropertyException $ex) {
        }
    }

    public function testAllowTags()
    {
        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string', 'allow_tags' => true],
        ]);

        $data      = $this->newData();
        $data->foo = '<h1>test</h1>';
        $this->assertTrue($data->validate());

        $this->setAttributes([
            'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
            'foo' => ['type' => 'string'],
        ]);

        $data->foo = '<h1>test</h1>';
        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UnexpectedPropertyValueException', '/cannot contain tags$/');
        $data->validate();
    }

    public function testFindOrCreate()
    {
        $this->setAttributes([
            'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
        ]);

        $class = $this->class;

        $data = $class::findOrCreate(999);
        $this->assertTrue($data->isFresh());
        $this->assertSame(999, $data->id());

        //////////////////////////////////////////////////////////////////////////////
        $this->setAttributes([
            'foo' => ['type' => 'uuid', 'primary_key' => true],
            'bar' => ['type' => 'uuid', 'primary_key' => true],
        ]);

        $class = $this->class;

        $data = $class::findOrCreate([
            'foo' => 'c8e94a82-0100-48a2-aaa1-a12adeb94300',
            'bar' => 'e864e07e-c9d1-44ae-aeaf-ca6610380d29',
        ]);
        $this->assertEquals('c8e94a82-0100-48a2-aaa1-a12adeb94300', $data->foo);
        $this->assertEquals('e864e07e-c9d1-44ae-aeaf-ca6610380d29', $data->bar);

        //////////////////////////////////////////////////////////////////////////////
        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\PropertyException', '/Illegal id/');
        $class::findOrCreate('9f8e2ba8-fbb2-49d5-9f39-83af4fb52bf9');
    }
}
namespace Tests\Mock\DataMapper;

class FooData extends \Owl\DataMapper\Data
{
    protected static $mapper_options = [
        'service'    => 'foo.service',
        'collection' => 'foo.collection',
    ];
    protected static $attributes = [
        'id'  => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
        'foo' => ['type' => 'string'],
    ];
}

class BarData extends FooData
{
    protected static $mapper_options = [
        'service'    => 'bar.service',
        'collection' => 'bar.collection',
    ];
    protected static $attributes = [
        'bar' => ['type' => 'string'],
    ];
}
