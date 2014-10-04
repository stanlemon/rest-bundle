<?php
namespace Lemon\RestBundle\Object;

use Doctrine\Common\Collections\ArrayCollection;

class Criteria extends ArrayCollection
{
    const DEFAULT_LIMIT = 25;
    const DEFAULT_OFFSET = 0;

    const ORDER_BY = "_orderBy";
    const ORDER_DIR = "_orderDir";
    const LIMIT = "_limit";
    const OFFSET = "_offset";

    const ORDER_DIR_ASC = 'ASC';
    const ORDER_DIR_DESC = 'DESC';

    protected $orderBy = null;
    protected $orderDir = self::ORDER_DIR_ASC;
    protected $limit = self::DEFAULT_LIMIT;
    protected $offset = self::DEFAULT_OFFSET;

    /**
     * @param array $elements
     */
    public function __construct(array $elements = array())
    {
        foreach ($elements as $key => $value) {
            switch ($key) {
                case self::ORDER_BY:
                    $this->orderBy = $value;
                    unset($elements[$key]);
                    break;
                case self::ORDER_DIR:
                    $this->orderDir = $value == self::ORDER_DIR_ASC ?: self::ORDER_DIR_DESC;
                    unset($elements[$key]);
                    break;
                case self::LIMIT:
                    $this->limit = (int) $value;
                    unset($elements[$key]);
                    break;
                case self::OFFSET:
                    $this->offset = (int) $value;
                    unset($elements[$key]);
                    break;
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
            return array($this->orderBy => $this->orderDir);
        } else {
            return null;
        }
    }
}
