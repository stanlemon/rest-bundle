<?php
namespace Lemon\RestBundle\Object;

use Symfony\Bridge\Doctrine\ManagerRegistry as Doctrine;
use Lemon\RestBundle\Event\ObjectEvent;
use Lemon\RestBundle\Event\PostSearchEvent;
use Lemon\RestBundle\Event\PreSearchEvent;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Model\SearchResults;
use Lemon\RestBundle\Object\Exception\NotFoundException;
use Lemon\RestBundle\Object\Exception\UnsupportedMethodException;
use Lemon\RestBundle\Object\Repository\OrmRepositoryWrapper;
use Lemon\RestBundle\Object\Repository\MongoRepositoryWrapper;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Manager implements ManagerInterface
{
    protected $doctrine;
    protected $eventDispatcher;
    protected $objectDefinition;

    /**
     * @param Doctrine $doctrine
     * @param EventDispatcher $eventDispatcher
     * @param Definition $objectDefinition
     */
    public function __construct(
        Doctrine $doctrine,
        EventDispatcher $eventDispatcher,
        Definition $objectDefinition
    ) {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->objectDefinition = $objectDefinition;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->objectDefinition->getClass();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->doctrine->getManagerForClass($this->getClass());
    }

    /**
     * @return \Lemon\RestBundle\Object\Repository
     */
    protected function getRepository()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository($this->getClass());

        if ($repository instanceof Repository) {
            return $repository;
        }

        if ($manager instanceof \Doctrine\ORM\EntityManager) {
            return new OrmRepositoryWrapper($repository);
        }

        if ($manager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            return new MongoRepositoryWrapper($repository);
        }

        throw new \RuntimeException("I have no idea what to do with this repository class!");
    }

    protected function throwUnsupportedMethodException()
    {
        throw new UnsupportedMethodException();
    }

    /**
     * @param Criteria $criteria
     * @return SearchResults
     */
    public function search(Criteria $criteria)
    {
        !$this->objectDefinition->canSearch() && $this->throwUnsupportedMethodException();

        $this->eventDispatcher->dispatch(RestEvents::PRE_SEARCH, new PreSearchEvent($criteria));

        $repository = $this->getRepository();

        $total = $repository->count($criteria);
        $objects = $repository->search($criteria);

        $results = new SearchResults($objects, $total);

        $this->eventDispatcher->dispatch(RestEvents::POST_SEARCH, new PostSearchEvent($results));

        return $results;
    }

    /**
     * @param object $object
     * @return mixed
     */
    public function create($object)
    {
        !$this->objectDefinition->canCreate() && $this->throwUnsupportedMethodException();

        $em = $this->getManager();

        $this->eventDispatcher->dispatch(RestEvents::PRE_CREATE, new ObjectEvent($object));

        $em->persist($object);
        $em->flush();
        $em->refresh($object);

        $this->eventDispatcher->dispatch(RestEvents::POST_CREATE, new ObjectEvent($object));

        return $object;
    }

    /**
     * @param integer $id
     * @return object
     */
    public function retrieve($id)
    {
        if (!($object = $this->getRepository()->findById($id))) {
            throw new NotFoundException("Object not found");
        }
        return $object;
    }

    /**
     * @param object $object
     * @return object
     */
    public function update($object)
    {
        !$this->objectDefinition->canUpdate() && $this->throwUnsupportedMethodException();

        $em = $this->getManager();

        $original = $this->retrieve(IdHelper::getId($object));

        if ($em->contains($object) === false) {
            $object = $em->merge($object);
        }

        $this->eventDispatcher->dispatch(RestEvents::PRE_UPDATE, new ObjectEvent($object, $original));

        $em->flush();
        $em->refresh($object);

        $this->eventDispatcher->dispatch(RestEvents::POST_UPDATE, new ObjectEvent($object, $original));

        return $object;
    }

    public function partialUpdate($object)
    {
        !$this->objectDefinition->canPartialUpdate() && $this->throwUnsupportedMethodException();

        $em = $this->getManager();

        if ($em->contains($object) === false) {
            $object = $em->merge($object);
        }

        $em->flush();
        $em->refresh($object);

        return $object;
    }

    /**
     * @param integer $id
     */
    public function delete($id)
    {
        !$this->objectDefinition->canDelete() && $this->throwUnsupportedMethodException();

        $object = $this->retrieve($id);

        $em = $this->getManager();

        if ($em->contains($object) === false) {
            $object = $em->merge($object);
        }

        $this->eventDispatcher->dispatch(RestEvents::PRE_DELETE, new ObjectEvent($object));

        $em->remove($object);
        $em->flush();

        $this->eventDispatcher->dispatch(RestEvents::POST_DELETE, new ObjectEvent($object));
    }

    /**
     * @param bool $isResource
     * @return array
     */
    public function getOptions($isResource = false)
    {
        return $this->objectDefinition->getOptions($isResource);
    }
}
