<?php
namespace Lemon\RestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PreSearchEvent extends Event
{
    protected $criteria;

    public function __construct($criteria)
    {
        $this->criteria = $criteria;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }
}
