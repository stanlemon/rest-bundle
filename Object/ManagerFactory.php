<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Lemon\RestBundle\Object\Criteria;
class ManagerFactory
{
    protected $registry;
    protected $eventDispatcher;
    protected $doctrine;

    public function __construct(
        Registry $registry,
        Doctrine $doctrine,
        EventDispatcher $eventDispatcher,
        Criteria $criteria
    ) {
        $this->registry = $registry;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->criteria = $criteria;
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
            $this->criteria,
            $class
        );
    }
}
