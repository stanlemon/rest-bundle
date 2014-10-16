<?php
namespace Lemon\RestBundle\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Lemon\RestBundle\Object\IdHelper;

class IdCollectionHandler implements SubscribingHandlerInterface
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;

    /**
     * @param Doctrine $doctrine
     */
    public function __construct(Doctrine $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Collection $idCollection
     * @param array $type
     * @param Context $context
     * @return array
     */
    public function serializeIdCollectionToJson(
        JsonSerializationVisitor $visitor,
        Collection $idCollection,
        array $type,
        Context $context
    ) {
        $ids = array();

        foreach ($idCollection as $id) {
            $ids[] = IdHelper::getId($id);
        }

        return $ids;
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param $data
     * @param array $type
     * @return ArrayCollection
     */
    public function deserializeIdCollectionFromJson(
        JsonDeserializationVisitor $visitor,
        $data,
        array $type
    ) {
        $collection = new ArrayCollection();

        $class = $type['params'][0]['name'];

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->doctrine->getManagerForClass($class)->getRepository($class);

        foreach ($data as $key => $value) {
            $collection->add($repository->findOneBy(array('id' => $value)));
        }

        return $collection;
    }

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Lemon\RestBundle\Serializer\IdCollection',
                'method' => 'serializeIdCollectionToJson',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'Lemon\RestBundle\Serializer\IdCollection',
                'method' => 'deserializeIdCollectionFromJson',
            ),
        );
    }
}
