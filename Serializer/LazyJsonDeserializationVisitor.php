<?php

namespace Lemon\RestBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Metadata\PropertyMetadata;

class LazyJsonDeserializationVisitor extends JsonDeserializationVisitor
{

    public function visitArray($data, array $type, Context $context)
    {
        $types = array('NULL', 'string', 'integer', 'boolean', 'double', 'float', 'array', 'ArrayCollection');

        if (is_array($data) && count($type['params']) === 1 && !empty($type['params'][0]['name'])) {
            foreach ($data as $key => $value) {
                if (is_scalar($value) && !in_array($type['params'][0]['name'], $types)) {
                    /** @var DeserializationContext $context */
                    $context->useDoctrineConstructor();

                    $data[$key] = array(
                        'id' => $value
                    );
                }
            }
        }

        return parent::visitArray($data, $type, $context);
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $name = $this->namingStrategy->translateName($metadata);

        $types = array('NULL', 'string', 'integer', 'boolean', 'double', 'float', 'array', 'ArrayCollection',
            'DateTime');

        if (isset($data[$name]) && is_scalar($data[$name]) && !in_array($metadata->type['name'], $types)) {
            /** @var DeserializationContext $context */
            $context->useDoctrineConstructor();

            $data[$name] = array(
                'id' => $data[$name],
            );
        }

        if (null === $data || (is_array($data) && !array_key_exists($name, $data))) {
            return;
        }

        if (!$metadata->type) {
            throw new RuntimeException(sprintf(
                'You must define a type for %s::$%s.',
                $metadata->reflection->class,
                $metadata->name
            ));
        }

        $v = $data[$name] !== null ? $this->getNavigator()->accept($data[$name], $metadata->type, $context) : null;

        if (null === $metadata->setter) {
            $metadata->reflection->setValue($this->getCurrentObject(), $v);

            return;
        }

        $this->getCurrentObject()->{$metadata->setter}($v);
    }
}
