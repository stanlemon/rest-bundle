<?php
namespace Lemon\RestBundle\Object;

interface Repository
{
	public function findById($id);

	public function count(Criteria $criteria);

	public function search(Criteria $criteria);
}
