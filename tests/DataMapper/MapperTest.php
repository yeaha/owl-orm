<?php
namespace Tests\DataMapper;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    protected $class = '\Tests\Mock\DataMapper\Data';

    protected function setAttributes(array $attributes)
    {
        ($this->class)::getMapper()->setAttributes($attributes);
    }

    public function testNormalizeID()
    {
        $this->setAttributes([
            'id' => ['type' => 'uuid', 'primary_key' => true],
        ]);

        $mapper = ($this->class)::getMapper();

        $id = $mapper->normalizeID('f6c7339d-9d68-41b1-9f16-860f29ea5dee');
        $this->assertSame(['id' => 'f6c7339d-9d68-41b1-9f16-860f29ea5dee'], $id);

        $id = $mapper->normalizeID(['id' => '3ed0d7a8-e51e-44ee-a2a1-09d435d94cc5']);
        $this->assertSame(['id' => '3ed0d7a8-e51e-44ee-a2a1-09d435d94cc5'], $id);

        ////////////////////////////////////////////////////////////////////////
        $this->setAttributes([
            'foo' => ['type' => 'uuid', 'primary_key' => true],
            'bar' => ['type' => 'uuid', 'primary_key' => true],
        ]);

        $id = [
            'foo' => 'cfd04278-6c98-4cf2-a26d-345c1ab729a2',
            'bar' => '7b76caa2-08fa-4e85-8cb8-744a1c5704ac',
            'baz' => 'ab8b9e5d-087a-458d-a595-29eda8ed38fb',
        ];

        $this->assertSame(
            [
                'foo' => 'cfd04278-6c98-4cf2-a26d-345c1ab729a2',
                'bar' => '7b76caa2-08fa-4e85-8cb8-744a1c5704ac',
            ],
            $mapper->normalizeID($id)
        );

        try {
            $mapper->normalizeID('1f47f18c-40d4-47c8-954b-c2d8a6fe4c2a');
            $this->fail('test Mapper::normalizeID() failed');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        try {
            $mapper->normalizeID(['foo' => '1f47f18c-40d4-47c8-954b-c2d8a6fe4c2a']);
            $this->fail('test Mapper::normalizeID() failed');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }
    }
}
