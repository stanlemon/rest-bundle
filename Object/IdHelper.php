<?php
namespace Lemon\RestBundle\Object;

class IdHelper
{
    const ID_PROPERTY = 'id';

    /**
     * @param object $object
     * @return \ReflectionProperty
     */
    protected static function getIdProperty($object)
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty(self::ID_PROPERTY);
        $property->setAccessible(true);
        return $property;
    }

    /**
     * @param object $object
     * @param int $id
     */
    public static function setId($object, $id)
    {
        self::getIdProperty($object)->setValue($object, $id);
    }

    /**
     * @param object $object
     * @return mixed
     */
    public static function getId($object)
    {
        return self::getIdProperty($object)->getValue($object);
    }
}
