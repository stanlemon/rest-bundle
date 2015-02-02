<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Lemon\RestBundle\Event\ObjectEvent;
use Lemon\RestBundle\Event\PostSearchEvent;
use Lemon\RestBundle\Event\PreSearchEvent;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Model\SearchResults;
use Lemon\RestBundle\Object\Exception\NotFoundException;
use Lemon\RestBundle\Object\Exception\UnsupportedMethodException;
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
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    protected function getManager()
    {
        return $this->doctrine->getManagerForClass($this->getClass());
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->doctrine->getManagerForClass($this->getClass())
            ->getRepository($this->getClass());
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

        /** @var \Doctrine\ORM\QueryBuilder $qb */
        $qb = $this->getManager()->createQueryBuilder('o');
        $qb->select($qb->expr()->count('o'))
            ->from($this->getClass(), 'o');

        $metadata = $this->getManager()->getClassMetadata($this->getClass());

        foreach ($criteria as $key => $value) {
            if ($metadata->hasField($key) || $metadata->hasAssociation($key)) {
                if ($metadata->getTypeOfField($key) == 'string') {
                    $qb->andWhere('o.' . $key . ' LIKE :' . $key);
                    $qb->setParameter($key, '%' . $value . '%');
                } else {
                    $qb->andWhere('o.' . $key . ' = :' . $key);
                    $qb->setParameter($key, $value);
                }
            } // Should we throw an Exception here?
        }
        $query = $qb->getQuery();

        $total = $query->getSingleScalarResult();

        $qb->select('o')
            ->setFirstResult($criteria->getOffset())
            ->setMaxResults($criteria->getLimit());

        $orderBy = $criteria->getOrderBy();

        if (is_array($orderBy)) {
            $qb->orderBy('o.' . key($orderBy), $orderBy[key($orderBy)]);
        }

        $query = $qb->getQuery();

        $objects = $query->execute();

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
        if (!($object = $this->getRepository()->findOneBy(array('id' => $id)))) {
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

        $this->eventDispatcher->dispatch(RestEvents::PRE_UPDATE, new ObjectEvent($object, $original));

        $object = $em->merge($object);

        $em->persist($object);
        $em->flush();
        $em->refresh($object);

        $this->eventDispatcher->dispatch(RestEvents::POST_UPDATE, new ObjectEvent($object, $original));

        return $object;
    }

    public function partialUpdate($object)
    {
        !$this->objectDefinition->canPartialUpdate() && $this->throwUnsupportedMethodException();

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getManager();

        $em->persist($object);
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

        $this->eventDispatcher->dispatch(RestEvents::PRE_DELETE, new ObjectEvent($object));

        $em->remove($object);
        $em->flush();

        $this->eventDispatcher->dispatch(RestEvents::POST_DELETE, new ObjectEvent($object));
    }
}
