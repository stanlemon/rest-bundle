<?php
namespace Lemon\RestBundle\Event;

use Lemon\RestBundle\Model\SearchResults;
use Symfony\Component\EventDispatcher\Event;

class PostSearchEvent extends Event
{
    protected $results;

    public function __construct(SearchResults $results)
    {
        $this->results = $results;
    }

    public function getResults()
    {
        return $this->results;
    }
}
