<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Lemon\RestBundle\Event\ObjectEvent;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Model\SearchResults;
use Lemon\RestBundle\Object\Criteria;
use Lemon\RestBundle\Object\Exception\NotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Manager
{
    protected $doctrine;
    protected $eventDispatcher;
    protected $class;

    /**
     * @param Doctrine $doctrine
     * @param EventDispatcher $eventDispatcher
     * @param string $class
     */
    public function __construct(
        Doctrine $doctrine,
        EventDispatcher $eventDispatcher,
        $class
    ) {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->class = $class;
    }

    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    protected function getManager()
    {
        return $this->doctrine->getManagerForClass($this->class);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->doctrine->getManagerForClass($this->class)
            ->getRepository($this->class);
    }

    /**
     * @param Criteria $criteria
     * @return SearchResults
     */
    public function search(Criteria $criteria)
    {
        $orderBy = null;
        $limit = 25;
        $offset = 0;

        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'orderBy':
                    $orderBy = $value;
                    $criteria->remove($key);
                    break;
                case 'limit':
                    $limit = (int) $value;
                    $criteria->remove($key);
                    break;
                case 'offset':
                    $offset = (int) $value;
                    $criteria->remove($key);
            }
        }

        $objects = $this->getRepository()->findBy($criteria->toArray(), $orderBy, $limit, $offset);

        $results = new SearchResults($objects);
        return $results;
    }

    public function create($object)
    {
        $em = $this->getManager();

        $this->eventDispatcher->dispatch(RestEvents::PRE_CREATE, new ObjectEvent($object));

        $em->persist($object);
        $em->flush();
        $em->refresh($object);

        $this->eventDispatcher->dispatch(RestEvents::POST_CREATE, new ObjectEvent($object));

        return $object;
    }

    public function retrieve($id)
    {
        if (!($object = $this->getRepository()->findOneBy(array('id' => $id)))) {
            throw new NotFoundException("Object not found");
        }
        return $object;
    }

    public function update($object)
    {
        $em = $this->getManager();

        $original = $this->retrieve($this->getIdFromObject($object));

        $this->eventDispatcher->dispatch(RestEvents::PRE_UPDATE, new ObjectEvent($object, $original));

        $object = $em->merge($object);

        $em->persist($object);
        $em->flush();
        $em->refresh($object);

        $this->eventDispatcher->dispatch(RestEvents::POST_UPDATE, new ObjectEvent($object, $original));

        return $object;
    }

    public function delete($id)
    {
        $object = $this->retrieve($id);

        $em = $this->getManager();

        $this->eventDispatcher->dispatch(RestEvents::PRE_DELETE, new ObjectEvent($object));

        $em->remove($object);
        $em->flush();

        $this->eventDispatcher->dispatch(RestEvents::POST_DELETE, new ObjectEvent($object));
    }

    protected function getIdFromObject($object)
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
