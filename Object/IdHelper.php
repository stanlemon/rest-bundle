<?php
namespace Lemon\RestBundle\Object;

class IdHelper
{
    const ID_PROPERTY = 'id';

    public static function setId($object, $id)
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty(self::ID_PROPERTY);
        $property->setAccessible(true);
        $property->setValue($object, $id);
    }

    public static function getId($object)
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty(self::ID_PROPERTY);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
