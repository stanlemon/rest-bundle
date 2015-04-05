<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Metadata\MetadataFactory;

class Processor
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;
    /**
     * @var \Metadata\MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @param Doctrine $doctrine
     * @param MetadataFactory $metadataFactory
     */
    public function __construct(Doctrine $doctrine, MetadataFactory $metadataFactory)
    {
        $this->doctrine = $doctrine;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @param object $object
     * @param object|null $entity
     */
    public function process($object, $entity = null)
    {
        $this->processIds($object);
        $this->processRelationships($object, $entity);
        $this->processExclusions($object, $entity);
    }

    /**
     * @param object $object
     */
    public function processIds($object)
    {
        $id = IdHelper::getId($object);

        if ($id !== null && empty($id)) {
            IdHelper::setId($object, null);
        }

        $class = get_class($object);
        $reflection = new \ReflectionClass($class);
        $em = $this->doctrine->getManagerForClass($class);

        /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata */
        $metadata = $em->getMetadataFactory()->getMetadataFor($class);

        // Look for relationships, compare against preloaded entity
        foreach ($metadata->getAssociationNames() as $fieldName) {
            $property = $reflection->getProperty($fieldName);
            $property->setAccessible(true);

            $value = $property->getValue($object);

            if (!$value) {
                continue;
            }

            if ($metadata->isCollectionValuedAssociation($fieldName)) {
                foreach ($value as $v) {
                    $this->processIds($v);
                }
            }
        }
    }

    /**
     * @param object $object
     * @param object|null $entity
     */
    public function processRelationships($object, $entity = null)
    {
        $class = get_class($object);

        $em = $this->doctrine->getManagerForClass($class);

        $reflection = new \ReflectionClass($class);

        /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata */
        $metadata = $em->getMetadataFactory()->getMetadataFor($class);

        // Look for relationships, compare against preloaded entity
        foreach ($metadata->getAssociationNames() as $fieldName) {
            $mappedBy = $metadata->getAssociationMappedByTargetField($fieldName);

            $property = $reflection->getProperty($fieldName);
            $property->setAccessible(true);

            $value = $property->getValue($object);

            if (!$value) {
                continue;
            }

            if ($metadata->isCollectionValuedAssociation($fieldName)) {
                foreach ($value as $k => $v) {
                    // If the parent object is new, or if the relation has already been persisted
                    // set the mappedBy to the current object so that ids fill in properly
                    if ($this->isNew($object) && isset($mappedBy)) {
                        $ref = new \ReflectionObject($v);
                        $prop = $ref->getProperty($mappedBy);
                        $prop->setAccessible(true);
                        $prop->setValue($v, $object);

                        $this->processRelationships($v);
                    }

                    $vid = IdHelper::getId($v);

                    // If we have an object that already exists, merge it before we persist
                    if ($vid !== null && $this->isNew($object)) {
                        $value[$k] = $em->merge($v);
                    }
                }

                // Existing objects with collections may need to have missing values removed from them
                if (!$this->isNew($object) && $entity) {
                    $original = $property->getValue($entity);

                    foreach ($original as $v) {
                        $checkIfExists = function ($key, $element) use ($v) {
                            return IdHelper::getId($v) == IdHelper::getId($element);
                        };
                        $exists = $value->exists($checkIfExists);
                        if ($value && $value != $object && !$exists) {
                            $em->remove($v);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param object $object
     * @return bool
     */
    protected function isNew($object)
    {
        return IdHelper::getId($object) === null;
    }

    /**
     * @param object $object
     * @param object|null $entity
     */
    protected function processExclusions($object, $entity)
    {
        $class = get_class($object);

        if (!$entity) {
            return;
        }

        $reflection = new \ReflectionClass($class);

        /** Backfill data that is ignored or read only from the serializer */
        $metadata = $this->metadataFactory->getMetadataForClass($class);

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            if (!isset($metadata->propertyMetadata[$name]) || $metadata->propertyMetadata[$name]->readOnly) {
                $property->setAccessible(true);
                $property->setValue(
                    $object,
                    $property->getValue($entity)
                );
            }
        }

        $em = $this->doctrine->getManagerForClass($class);
        /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata */
        $metadata = $em->getMetadataFactory()->getMetadataFor($class);

        // Look for relationships, compare against preloaded entity
        foreach ($metadata->getAssociationNames() as $fieldName) {
            if ($metadata->isCollectionValuedAssociation($fieldName)) {
                $property = $reflection->getProperty($fieldName);
                $property->setAccessible(true);

                if ($property->getValue($object)) {
                    foreach ($property->getValue($object) as $i => $value) {
                        $v = $property->getValue($entity);
                        $this->processExclusions(
                            $value,
                            $v[$i]
                        );
                    }
                }
            }
        }
    }
}
