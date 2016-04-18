<?php

namespace Lemon\RestBundle\Serializer\Construction;

use JMS\Serializer\Construction\DoctrineObjectConstructor;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\VisitorInterface;

class ToggleDoctrineObjectConstructor implements ObjectConstructorInterface
{
    /** @var ObjectConstructorInterface */
    protected $objectConstructor;
    /** @var DoctrineObjectConstructor  */
    protected $doctrineObjectConstructor;
    /** @var UnserializeObjectConstructor  */
    protected $unserializeObjectConstructor;

    /**
     * @param DoctrineObjectConstructor $doctrineObjectConstructor
     * @param UnserializeObjectConstructor $unserializeObjectConstructor
     */
    public function __construct(
        DoctrineObjectConstructor $doctrineObjectConstructor,
        UnserializeObjectConstructor $unserializeObjectConstructor
    ) {
        $this->doctrineObjectConstructor = $doctrineObjectConstructor;
        $this->unserializeObjectConstructor = $unserializeObjectConstructor;
        $this->objectConstructor = $this->unserializeObjectConstructor;
    }

    public function useDoctrine()
    {
        $this->objectConstructor = $this->doctrineObjectConstructor;
    }

    public function isDoctrine()
    {
        return $this->objectConstructor === $this->doctrineObjectConstructor;
    }

    public function useDefault()
    {
        $this->objectConstructor = $this->unserializeObjectConstructor;
    }

    public function isDefault()
    {
        return $this->objectConstructor === $this->unserializeObjectConstructor;
    }

    public function construct(
        VisitorInterface $visitor,
        ClassMetadata $metadata,
        $data,
        array $type,
        DeserializationContext $context
    ) {
        if ($context instanceof \Lemon\RestBundle\Serializer\DeserializationContext &&
            $context->shouldUseDoctrineConstructor()
        ) {
            $this->useDoctrine();
        }

        return $this->objectConstructor->construct(
            $visitor,
            $metadata,
            $data,
            $type,
            $context
        );
    }
}
