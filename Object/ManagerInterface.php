<?php
namespace Lemon\RestBundle\Object;

interface ManagerInterface
{
    /**
     * @return string
     */
    public function getClass();

    /**
     * @param Criteria $criteria
     * @return \Lemon\RestBundle\Model\SearchResults
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
