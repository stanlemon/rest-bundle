<?php

namespace Lemon\RestBundle\Serializer;

use JMS\Serializer\DeserializationContext as BaseDeserializationContext;

class DeserializationContext extends BaseDeserializationContext
{
    protected $useDoctrineConstructor = false;

    public function useDoctrineConstructor()
    {
        $this->useDoctrineConstructor = true;
    }

    public function shouldUseDoctrineConstructor()
    {
        return $this->useDoctrineConstructor;
    }
}
