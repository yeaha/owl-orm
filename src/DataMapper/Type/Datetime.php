<?php
namespace Owl\DataMapper\Type;

class Datetime extends Common
{
    public function normalize($value, array $attribute)
    {
        if ($this->isNull($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (!isset($attribute['format'])) {
            return new \DateTimeImmutable($value);
        }

        if (!$value = \DateTimeImmutable::createFromFormat($attribute['format'], $value)) {
            throw new \UnexpectedValueException('Create datetime from format "' . $attribute['format'] . '" failed!');
        }

        return $value;
    }

    public function store($value, array $attribute)
    {
        if ($value instanceof \DateTimeInterface) {
            $format = isset($attribute['format']) ? $attribute['format'] : 'c'; // ISO 8601
            $value  = $value->format($format);
        }

        return $value;
    }

    public function getDefaultValue(array $attribute)
    {
        return ($attribute['default'] === null)
             ? null
             : new \DateTimeImmutable($attribute['default']);
    }

    public function toJSON($value, array $attribute)
    {
        return $this->store($value, $attribute);
    }
}
