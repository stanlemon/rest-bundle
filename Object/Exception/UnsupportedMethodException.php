<?php
namespace Lemon\RestBundle\Object\Exception;

class UnsupportedMethodException extends \RuntimeException
{
    public function __construct($message = "Method not supported", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
