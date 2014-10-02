<?php
namespace Lemon\RestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PostSearchEvent extends Event
{
    protected $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function getResults()
    {
        return $this->results;
    }
}
