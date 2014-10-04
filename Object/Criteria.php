<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Common\Collections\ArrayCollection;

class Criteria extends ArrayCollection
{
    protected $orderBy = null;
    protected $limit = 25;
    protected $offset = 0;

    /**
     * @param array $elements
     */
    public function __construct(array $elements = array())
    {
        foreach ($elements as $key => $value) {
            switch ($key) {
                case 'orderBy':
                    $this->orderBy = $value;
                    unset($elements[$key]);
                    break;
                case 'limit':
                    $this->limit = (int) $value;
                    unset($elements[$key]);
                    break;
                case 'offset':
                    $this->offset = (int) $value;
                    unset($elements[$key]);
            }
        }

        parent::__construct($elements);
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return null
     */
    public function getOrderBy()
    {
        if ($this->orderBy) {
            return array($this->orderBy => 'ASC');
        } else {
            return null;
        }
    }
}
