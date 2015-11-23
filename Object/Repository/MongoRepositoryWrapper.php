<?php
namespace Lemon\RestBundle\Object\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Lemon\RestBundle\Object\Repository;
use Lemon\RestBundle\Object\Criteria;

class MongoRepositoryWrapper implements Repository
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
		$this->metadata = $repository->getClassMetadata(); 
	}

	public function count(Criteria $criteria)
	{
		$qb = $this->repository->createQueryBuilder();
	
		$this->buildWhereClause($qb, $criteria);
	
		$qb->select();

		return $qb->getQuery()
		   ->execute()
		   ->count();
	}

	/**
	 * @param Criteria $criteria
	 */
	public function search(Criteria $criteria)
	{
		$qb = $this->repository->createQueryBuilder();
	
		$this->buildWhereClause($qb, $criteria);
	
		$qb->select();
		
		if ($criteria->getOrderBy()) {
    	   $qb->sort($criteria->getOrderBy(), $criteria->getOrderDir());
		}
		
		if ($criteria->getOffset()) {
		   $qb->skip($criteria->getOffset());
		}

		if ($criteria->getLimit()) {
		   $qb->limit($criteria->getLimit());
		}
		
		$cursor = $qb->getQuery()
		   ->execute();

		$results = array();

		foreach ($cursor as $value) {
			$results[] = $value;
		}

		return $results;
	}

	/**
	 * @param QueryBuilder $qb
	 * @param Criteria $criteria
	 */
	protected function buildWhereClause(Builder $qb, Criteria $criteria)
	{
        foreach ($criteria as $key => $value) {
            if ($this->metadata->hasField($key) || $this->metadata->hasAssociation($key)) {
				$qb->field($key)->equals($value);
			}
        }
	}

	public function findById($id)
	{
		return $this->repository->findOneBy(array('id' => $id));
	}
}
