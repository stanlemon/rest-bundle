<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Common\Collections\Collection;

interface Criteria extends Collection
{
    /**
     * @param array $elements
     */
    public function __construct(array $elements = array());

    public function getLimit();

    public function getOffset();

    public function getOrderBy();

    public function getOrderDir();
}
