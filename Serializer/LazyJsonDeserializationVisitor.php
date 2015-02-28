<?php

namespace Lemon\RestBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Metadata\PropertyMetadata;

class LazyJsonDeserializationVisitor extends JsonDeserializationVisitor
{
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $name = $this->namingStrategy->translateName($metadata);

        $types = array('NULL', 'string', 'integer', 'boolean', 'double', 'float', 'array');

        if (isset($data[$name]) && is_scalar($data[$name]) && !in_array($metadata->type['name'], $types)) {
            /** @var DeserializationContext $context */
            $context->useDoctrineConstructor();

            $data[$name] = array(
                'id' => $data[$name],
            );
        }

        if (null === $data || ! array_key_exists($name, $data)) {
            return;
        }

        if ( ! $metadata->type) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->reflection->class, $metadata->name));
        }

        $v = $data[$name] !== null ? $this->getNavigator()->accept($data[$name], $metadata->type, $context) : null;

        if (null === $metadata->setter) {
            $metadata->reflection->setValue($this->getCurrentObject(), $v);

            return;
        }

        $this->getCurrentObject()->{$metadata->setter}($v);
    }
}
