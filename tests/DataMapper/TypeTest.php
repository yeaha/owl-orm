<?php
namespace Tests;

use Owl\DataMapper;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeAttribute()
    {
        $attribute = DataMapper\Type::normalizeAttribute(['primary_key' => true]);
        $this->assertFalse($attribute['allow_null']);
        $this->assertTrue($attribute['refuse_update']);
        $this->assertTrue($attribute['strict']);

        $attribute = DataMapper\Type::normalizeAttribute(['protected' => true]);
        $this->assertTrue($attribute['strict']);

        $attribute = DataMapper\Type::normalizeAttribute(['default' => 'foo', 'allow_null' => true]);
        $this->assertNull($attribute['default']);

        $attribute = DataMapper\Type::normalizeAttribute(['pattern' => '/\d+/']);
        $this->assertTrue(isset($attribute['regexp']));
        $this->assertFalse(isset($attribute['pattern']));
    }

    public function testCommon()
    {
        $type = $this->getType(null);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $type = $this->getType('undefined type name');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $attribute = ['foo' => 'bar'];
        $this->assertSame($attribute, $type->normalizeAttribute($attribute));
        $this->assertSame('foo', $type->normalize('foo', []));
        $this->assertSame('foo', $type->store('foo', []));
        $this->assertSame('foo', $type->restore('foo', []));
        $this->assertSame('foo', $type->toJSON('foo', []));

        $this->assertSame('foo', $type->getDefaultValue(['default' => 'foo']));
    }

    public function testNumber()
    {
        $type = $this->getType('number');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Number', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $this->assertSame(1.11, $type->normalize('1.11', []));

        $this->assertInstanceOf('\Owl\DataMapper\Type\Number', $this->getType('numeric'));
    }

    public function testInteger()
    {
        $type = $this->getType('integer');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Integer', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $this->assertSame(1, $type->normalize('1.11', []));
    }

    public function testString()
    {
        $type = $this->getType('string');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Text', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $this->assertSame('1.11', $type->normalize(1.11, []));
    }

    public function testUUID()
    {
        $type = $this->getType('uuid');
        $this->assertInstanceOf('\Owl\DataMapper\Type\UUID', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $attribute = $type->normalizeAttribute(['primary_key' => true]);
        $this->assertTrue($attribute['auto_generate']);

        $re = '/^[0-9A-F\-]{36}$/';
        $this->assertRegExp($re . 'i', $type->getDefaultValue(['auto_generate' => true]));
        $this->assertRegExp($re, $type->getDefaultValue(['auto_generate' => true, 'upper' => true]));
    }

    public function testDateTime()
    {
        $type = $this->getType('datetime');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Datetime', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Common', $type);

        $now = new \Datetime();
        $this->assertSame($now, $type->normalize($now, []));

        $this->assertInstanceOf('\DatetimeInterface', $type->normalize('now', []));

        $this->assertRegExp('/^\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2}[+\-]\d{1,2}(?::\d{1,2})?$/', $type->store($now, []));
        $this->assertRegExp('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $type->store($now, ['format' => 'Y-m-d']));
        $this->assertRegExp('/^\d+$/', $type->store($now, ['format' => 'U']));

        $this->assertInstanceOf('\DateTimeImmutable', $type->restore('2014-01-01T00:00:00+0', []));

        $ts   = 1388534400;
        $time = $type->restore($ts, ['format' => 'U']);

        $this->assertInstanceOf('\DateTimeImmutable', $time);
        $this->assertEquals($ts, $time->getTimestamp());

        $this->setExpectedException('\UnexpectedValueException');
        $type->normalize($ts, ['format' => 'c']);
    }

    public function testJSON()
    {
        $type = $this->getType('json');
        $this->assertInstanceOf('\Owl\DataMapper\Type\JSON', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Complex', $type);

        $json = ['foo' => 'bar'];
        $this->assertEquals($json, $type->normalize($json, []));
        $this->assertEquals($json, $type->normalize(json_encode($json), []));

        $this->assertNull($type->store([], []));
        $this->assertEquals(json_encode($json), $type->store($json, []));

        $this->setExpectedException('\UnexpectedValueException');
        $type->restore('{"a"', []);

        $this->assertSame([], $type->getDefaultValue([]));
        $this->assertSame([], $type->getDefaultValue(['allow_null' => true]));
    }

    public function testRestoreNull()
    {
        $expect = [
            'mixed'     => null,
            'string'    => null,
            'integer'   => null,
            'numerci'   => null,
            'uuid'      => null,
            'datetime'  => null,
            'json'      => [],
            'pg_array'  => [],
            'pg_hstore' => [],
        ];

        foreach ($expect as $type => $value) {
            $this->assertSame($value, $this->getType($type)->restore(null, []));
        }
    }

    protected function getType($name)
    {
        return DataMapper\Type::getInstance()->get($name);
    }
}
