<?php
namespace Lemon\RestBundle\Object;

interface ManagerFactoryInterface
{
    /**
     * @param string $resource
     * @return ManagerInterface
     */
    public function create($resource);
}
