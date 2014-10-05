<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Doctrine\ORM\UnitOfWork;
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

    public function __construct(Doctrine $doctrine, MetadataFactory $metadataFactory)
    {
        $this->doctrine = $doctrine;
        $this->metadataFactory = $metadataFactory;
    }

    public function process($object, $entity = null)
    {
        $this->processIds($object);
        $this->processRelationships($object, $entity);
        $this->processExclusions($object, $entity);
    }

    public function processIds($object)
    {
        $id = IdHelper::getId($object);

        if ($id !== null && empty($id)) {
            IdHelper::setId($object, null);
        }

        $class = get_class($object);
        $reflection = new \ReflectionClass($class);
        $em = $this->doctrine->getManagerForClass($class);

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $em->getMetadataFactory()->getMetadataFor($class);

        // Look for relationships, compare against preloaded entity
        foreach ($metadata->getAssociationMappings() as $association) {
            $property = $reflection->getProperty($association['fieldName']);
            $property->setAccessible(true);

            $value = $property->getValue($object);

            if (!$value) {
                continue;
            }

            if (in_array($association['type'], array(4, 8))) {
                foreach ($value as $v) {
                    $this->processIds($v);
                }
            } else {
                $this->processIds($value);
            }
        }
    }

    /**
     * @param $object
     * @param null $entity
     */
    public function processRelationships($object, $entity = null)
    {
        $class = get_class($object);

        $em = $this->doctrine->getManagerForClass($class);

        $reflection = new \ReflectionClass($class);

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $em->getMetadataFactory()->getMetadataFor($class);

        // Look for relationships, compare against preloaded entity
        foreach ($metadata->getAssociationMappings() as $association) {
            $property = $reflection->getProperty($association['fieldName']);
            $property->setAccessible(true);

            $value = $property->getValue($object);

            if (!$value) {
                continue;
            }

            if (in_array($association['type'], array(4, 8))) {
                foreach ($value as $k => $v) {
                    // If the parent object is new, or if the relation has already been persisted
                    // set the mappedBy to the current object so that ids fill in properly
                    if ($this->isNew($object) && isset($association['mappedBy'])) {
                        $ref = new \ReflectionObject($v);
                        $prop = $ref->getProperty($association['mappedBy']);
                        $prop->setAccessible(true);
                        $prop->setValue($v, $object);
                    }

                    $vid = IdHelper::getId($v);

                    // If we have an object that already exists, merge it before we persist
                    if ($vid !== null) {
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
            } else {
                $this->processRelationships($value, $entity ? $property->getValue($entity) : null);
            }
        }
    }

    /**
     * @param $object
     * @return bool
     */
    protected function isNew($object)
    {
        $em = $this->doctrine->getManagerForClass(get_class($object));

        return $em->getUnitOfWork()->getEntityState($object) === UnitOfWork::STATE_NEW;
    }

    /**
     * @param $object
     * @param $entity
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
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $em->getMetadataFactory()->getMetadataFor($class);

        foreach ($metadata->getAssociationMappings() as $association) {
            if (in_array($association['type'], array(4, 8))) {
                $property = $reflection->getProperty($association['fieldName']);
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
