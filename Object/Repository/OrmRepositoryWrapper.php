<?php
namespace Lemon\RestBundle\Object\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Lemon\RestBundle\Object\Repository;
use Lemon\RestBundle\Object\Criteria;

class OrmRepositoryWrapper implements Repository
{
    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @param ObjectRepository $repository
     */
    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;

        $reflection = new \ReflectionObject($repository);
        $property = $reflection->getProperty('_class');
        $property->setAccessible(true);

        $this->metadata = $property->getValue($repository);
    }

    /**
     * @param Criteria $criteria
     */
    public function count(Criteria $criteria)
    {
        $identifiers = $this->metadata->getIdentifier();
        $identifierName = reset($identifiers);

        $qb = $this->repository->createQueryBuilder('e');

        $this->buildWhereClause($qb, $criteria);

        return $qb->select("count(e.{$identifierName})")
           ->getQuery()
           ->getSingleScalarResult();
    }

    /**
     * @param Criteria $criteria
     */
    public function search(Criteria $criteria)
    {
        $qb = $this->repository->createQueryBuilder('e');

        $this->buildWhereClause($qb, $criteria);

        $qb->select();

        if ($criteria->getOrderBy()) {
            $qb->orderBy('e.' . $criteria->getOrderBy(), $criteria->getOrderDir());
        }

        if ($criteria->getOffset()) {
            $qb->setFirstResult($criteria->getOffset());
        }

        if ($criteria->getLimit()) {
            $qb->setMaxResults($criteria->getLimit());
        }

        return $qb->getQuery()
           ->execute();
    }

    /**
     * @param QueryBuilder $qb
     * @param Criteria $criteria
     */
    protected function buildWhereClause(QueryBuilder $qb, Criteria $criteria)
    {
        $values = array();

        foreach ($criteria as $key => $value) {
            if ($this->metadata->hasField($key) || $this->metadata->hasAssociation($key)) {
                $qb->andWhere('e.' . $key . ' = :' . $key);
                $values[$key] = $value;
            }
        }

        $qb->setParameters($values);
    }

    /**
     * @param int $id
     */
    public function findById($id)
    {
        return $this->repository->findOneBy(array('id' => $id));
    }
}
