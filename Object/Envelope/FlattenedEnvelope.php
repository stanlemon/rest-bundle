<?php
namespace Lemon\RestBundle\Object\Envelope;

use Lemon\RestBundle\Model\SearchResults;
use Lemon\RestBundle\Object\Envelope;

class FlattenedEnvelope implements Envelope
{
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function export()
    {
        if ($this->payload instanceof SearchResults) {
            return $this->payload->getResults();
        } else {
            return $this->payload;
        }
    }
}
