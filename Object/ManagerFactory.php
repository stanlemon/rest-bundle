<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ManagerFactory
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var Doctrine
     */
    protected $doctrine;

    /**
     * @param Registry $registry
     * @param Doctrine $doctrine
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        Registry $registry,
        Doctrine $doctrine,
        EventDispatcher $eventDispatcher
    ) {
        $this->registry = $registry;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $resource
     * @return Manager
     */
    public function create($resource)
    {
        $class = $this->registry->getClass($resource);

        return new Manager(
            $this->doctrine,
            $this->eventDispatcher,
            $class
        );
    }
}
