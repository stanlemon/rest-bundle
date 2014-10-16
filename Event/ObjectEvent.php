<?php
namespace Lemon\RestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ObjectEvent extends Event
{
    /**
     * @var object
     */
    protected $object;
    /**
     * @var object|null
     */
    protected $original;

    /**
     * @param object $object
     * @param object|null $original
     */
    public function __construct($object, $original = null)
    {
        $this->object = $object;
        $this->original = $original;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getOriginal()
    {
        return $this->original;
    }
}
