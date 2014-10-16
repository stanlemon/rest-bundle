<?php
namespace Lemon\RestBundle\Object\Envelope;

use Lemon\RestBundle\Object\Envelope;

class DefaultEnvelope implements Envelope
{
    protected $payload;

    /**
     * @param mixed $payload
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function export()
    {
        return $this->payload;
    }
}
