<?php
namespace Lemon\RestBundle\Object\Exception;

class InvalidException extends \RuntimeException
{
    protected $errors;

    /**
     * @param string $message
     * @param array $errors
     */
    public function __construct($message, $errors = array())
    {
        parent::__construct($message, 0, null);
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
