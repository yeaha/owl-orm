<?php
namespace Tests\DataMapper;

use Owl\DataMapper\Registry;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    protected $class = '\Tests\Mock\DataMapper\Data';

    protected function setAttributes(array $attributes)
    {
        ($this->class)::getMapper()->setAttributes($attributes);
    }

    public function test()
    {
        $this->setAttributes([
            'id' => ['type' => 'uuid', 'primary_key' => true],
        ]);
        $id = '710c825e-20ea-4c98-b313-30d9eec2b2dc';

        $class = $this->class;
        $data  = new $class([
            'id' => $id,
        ]);

        $registry = Registry::getInstance();

        $this->assertFalse((bool) $registry->get($class, $data->id(true)));
        $data->save();
        $this->assertFalse((bool) $registry->get($class, $data->id(true)));

        $data = $class::find($id);
        $this->assertTrue((bool) $registry->get($class, $data->id(true)));

        $data->destroy();
        $this->assertFalse((bool) $registry->get($class, ['id' => $id]));
    }
}
