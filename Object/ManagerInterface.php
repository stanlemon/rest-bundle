<?php
namespace Lemon\RestBundle\Object;

use Lemon\RestBundle\Model\SearchResults;

interface ManagerInterface
{
    /**
     * @return string
     */
    public function getClass();

    /**
     * @param Criteria $criteria
     * @return SearchResults
     */
    public function search(Criteria $criteria);

    /**
     * @param object $object
     * @return mixed
     */
    public function create($object);

    /**
     * @param integer $id
     * @return object
     */
    public function retrieve($id);

    /**
     * @param object $object
     * @return object
     */
    public function update($object);

    /**
     * @param object $object
     * @return object
     */
    public function partialUpdate($object);

    /**
     * @param integer $id
     */
    public function delete($id);
}
