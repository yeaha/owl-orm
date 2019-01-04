<?php

namespace Owl\DataMapper\DB;

class Select extends \Owl\Service\DB\Select
{
    /**
     * @return \Owl\DataMapper\DB\Data[]
     */
    public function get($limit = null)
    {
        $result = [];

        foreach (parent::get($limit) as $data) {
            $result[$data->id()] = $data;
        }

        return $result;
    }
}
