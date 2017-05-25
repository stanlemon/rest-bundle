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
     * @param Criteria     $criteria
     */
    protected function buildWhereClause(QueryBuilder $qb, Criteria $criteria)
    {
        foreach ($criteria as $field => $value) {
            $value = trim($value);

            if (strlen($value) > 0) {
                $fields = [$field];

                if (strpos($field, '_id') !== false) {
                    $fields[] = str_replace('_id', '', $field);
                }

                foreach ($fields as $fieldName) {
                    if ($this->metadata->hasAssociation($fieldName)) {
                        $qb->andWhere('e.' . $fieldName . ' = :' . $fieldName);
                        $qb->setParameter($fieldName, $value);
                    } else {
                        $fieldMetaData = array_filter($this->metadata->fieldMappings, function ($mapping) use ($fieldName) {
                            return (
                                ($mapping['fieldName'] === $fieldName)
                                || ($mapping['columnName'] === $fieldName)
                            );
                        });

                        if (count($fieldMetaData) === 1) {
                            $fieldMetaData = current($fieldMetaData);
                            $fieldName = $fieldMetaData['fieldName'];

                            if ($fieldMetaData['type'] === 'string') {
                                $qb->andWhere('e.'.$fieldName.' LIKE :'.$fieldName);
                                $qb->setParameter($fieldName, sprintf('%%%s%%', $value));
                            } elseif ($fieldMetaData['type'] === 'boolean') {
                                $value = strtolower($value);

                                if (in_array($value, ['1', 'true', '0', 'false'])) {
                                    $qb->andWhere('e.' . $fieldName . ' = :' . $fieldName);
                                    $qb->setParameter($fieldName, in_array($value, ['1', 'true']));
                                }
                            } elseif (
                                ($fieldMetaData['type'] === 'date')
                                || ($fieldMetaData['type'] === 'datetime')
                                || ($fieldMetaData['type'] === 'datetimez')
                            ) {
                                $date = new \DateTime($value);

                                $qb->andWhere('e.'.$fieldName.' LIKE :'.$fieldName);
                                $qb->setParameter($fieldName, sprintf('%s%%', $date->format('Y-m-d')));
                            } else {
                                $qb->andWhere('e.' . $fieldName . ' = :' . $fieldName);
                                $qb->setParameter($fieldName, $value);
                            }
                        }
                    }
                }
            }
        }
    }

	/**
	 * @param int $id
	 */
	public function findById($id)
	{
		return $this->repository->findOneBy(array('id' => $id));
	}
}
