<?php
namespace Tests\Mock\DataMapper;

class Service extends \Owl\Service
{
    protected $data = [];

    public function __construct(array $config = [])
    {
    }

    public function disconnect()
    {
    }

    public function find($table, array $id)
    {
        $key = $this->keyOfId($id);

        if (!isset($this->data[$table][$key])) {
            return false;
        }

        return $this->data[$table][$key];
    }

    public function insert($table, array $row, array $id = null)
    {
        foreach ($id as $k => $v) {
            if (!$v) {
                $id[$k] = StorageSequence::getInstance()->next();
            }
        }

        $key = $this->keyOfId($id);

        $this->data[$table][$key] = $row;

        return $id;
    }

    public function update($table, array $row, array $id)
    {
        if (!$this->find($table, $id)) {
            return false;
        }

        $key                      = $this->keyOfId($id);
        $this->data[$table][$key] = array_merge($this->data[$table][$key], $row);

        return true;
    }

    public function delete($table, array $id)
    {
        $key = $this->keyOfId($id);

        if (!isset($this->data[$table][$key])) {
            return false;
        }

        unset($this->data[$table][$key]);

        return true;
    }

    public function getLastId()
    {
        return StorageSequence::getInstance()->current();
    }

    public function clear($table = null)
    {
        if ($table) {
            $this->data[$table] = [];
        } else {
            $this->data = [];
        }
    }

    protected function keyOfId(array $id)
    {
        ksort($id);

        return md5(strtolower(json_encode($id)));
    }
}

class StorageSequence
{
    use \Owl\Traits\Singleton;

    protected $seq = 0;

    public function current()
    {
        return $this->seq;
    }

    public function next()
    {
        return ++$this->seq;
    }
}
