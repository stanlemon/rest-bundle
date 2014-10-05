<?php
namespace Lemon\RestBundle\Event;

use Lemon\RestBundle\Object\Criteria;
use Symfony\Component\EventDispatcher\Event;

class PreSearchEvent extends Event
{
    protected $criteria;

    public function __construct(Criteria $criteria)
    {
        $this->criteria = $criteria;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }
}
