<?php
namespace Lemon\RestBundle\Serializer;

use Metadata\MetadataFactoryInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\Serializer;
use PhpCollection\MapInterface;

class ConstructorFactory
{
    protected $factory;
    protected $handlerRegistry;
    protected $eventDispatcher;
    protected $objectConstructorMap;
    protected $serializationVisitors;
    protected $deserializationVisitors;

    public function __construct(
        MetadataFactoryInterface $factory,
        HandlerRegistryInterface $handlerRegistry,
        MapInterface $serializationVisitors,
        MapInterface $deserializationVisitors,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->factory = $factory;
        $this->handlerRegistry = $handlerRegistry;
        $this->serializationVisitors = $serializationVisitors;
        $this->deserializationVisitors = $deserializationVisitors;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addObjectConstructor($alias, ObjectConstructorInterface $objectConstructor)
    {
        $this->objectConstructorMap[$alias] = $objectConstructor;
    }

    public function create($alias = 'default')
    {
        if (!isset($this->objectConstructorMap[$alias])) {
            throw new \RuntimeException("No ObjectConstructor was configured for alias \"{$alias}\"");
        }

        return new Serializer(
            $this->factory,
            $this->handlerRegistry,
            $this->objectConstructorMap[$alias],
            $this->serializationVisitors,
            $this->deserializationVisitors,
            $this->eventDispatcher
        );
    }
}
