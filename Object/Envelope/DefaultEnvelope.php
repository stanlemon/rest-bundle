<?php
namespace Lemon\RestBundle\Object\Envelope;

use Lemon\RestBundle\Model\SearchResults;
use Lemon\RestBundle\Object\Envelope;

class DefaultEnvelope implements Envelope
{
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function export()
    {
        return $this->payload;
    }
}
