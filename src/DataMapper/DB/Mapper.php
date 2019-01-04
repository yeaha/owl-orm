<?php

namespace Owl\DataMapper\DB;

class Mapper extends \Owl\DataMapper\Mapper
{
    /**
     * @return \Owl\DataMapper\DB\Select
     */
    public function select(\Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $primary_key = $this->getPrimaryKey();

        // 只有一个主键，就可以返回以主键为key的数组结果
        if (1 === count($primary_key)) {
            $select = new \Owl\DataMapper\DB\Select($service, $collection);
        } else {
            $select = new \Owl\Service\DB\Select($service, $collection);
        }

        $select->setColumns(array_keys($this->getAttributes()));

        $mapper = $this;
        $select->setProcessor(function ($record) use ($mapper) {
            return $record ? $mapper->pack($record) : false;
        });

        return $select;
    }

    public function getBySQLAsIterator($sql, array $parameters = [], \Owl\Service $service = null)
    {
        $service = $service ?: $this->getService();
        $res = $service->execute($sql, $parameters);

        while ($record = $res->fetch()) {
            yield $this->pack($record);
        }
    }

    protected function doFind(array $id, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        $select = $this->select($service, $collection);

        list($where, $params) = $this->whereID($service, $id);
        $select->where($where, $params);

        return $select->limit(1)->execute()->fetch();
    }

    protected function doInsert(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data);

        if (!$service->insert($collection, $record)) {
            return false;
        }

        $id = [];
        foreach ($this->getPrimaryKey() as $key) {
            if (!isset($record[$key])) {
                if (!$last_id = $service->lastId($collection, $key)) {
                    throw new \Exception("{$this->class}: Insert record success, but get last-id failed!");
                }
                $id[$key] = $last_id;
            }
        }

        return $id;
    }

    protected function doUpdate(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data, ['dirty' => true]);

        list($where, $params) = $this->whereID($service, $data->id(true));

        return $service->update($collection, $record, $where, $params);
    }

    protected function doDelete(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        list($where, $params) = $this->whereID($service, $data->id(true));

        return $service->delete($collection, $where, $params);
    }

    protected function whereID(\Owl\Service $service, array $id)
    {
        $where = $params = [];
        $primary_key = $this->getPrimaryKey();

        foreach ($primary_key as $key) {
            $where[] = $service->quoteIdentifier($key) . ' = ?';
            $params[] = $id[$key];
        }
        $where = implode(' AND ', $where);

        return [$where, $params];
    }
}
