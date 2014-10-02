<?php
namespace Lemon\RestBundle\Model;

class SearchResults
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
