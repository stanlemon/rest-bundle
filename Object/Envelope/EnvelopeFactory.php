<?php
namespace Lemon\RestBundle\Object\Envelope;

class EnvelopeFactory
{
    /**
     * @var string
     */
    protected $envelope;

    /**
     * @param string $envelope
     */
    public function __construct($envelope)
    {
        $this->envelope = $envelope;
    }

    /**
     * @return \Lemon\RestBundle\Object\Envelope
     */
    public function create($payload)
    {
        if (!class_exists($this->envelope)) {
            throw new \RuntimeException(sprintf("%s does not exist", $this->envelope));
        }
        if (!is_a($this->envelope, 'Lemon\RestBundle\Object\Envelope', true)) {
            throw new \RuntimeException(sprintf("%s is not a valid envelope"));
        }

        return new $this->envelope($payload);
    }
}
