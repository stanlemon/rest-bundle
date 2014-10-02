<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Common\Collections\ArrayCollection;

class Criteria extends ArrayCollection
{
    protected $class;

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getClass()
    {
        return $this->class;
    }
}
