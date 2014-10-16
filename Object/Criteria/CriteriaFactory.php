<?php
namespace Lemon\RestBundle\Object\Criteria;

class CriteriaFactory
{
    /**
     * @var string
     */
    protected $criteria;

    /**
     * @param string $criteria
     */
    public function __construct($criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @param array $elements
     * @return mixed
     */
    public function create(array $elements)
    {
        if (!class_exists($this->criteria)) {
            throw new \RuntimeException(sprintf("%s does not exist", $this->criteria));
        }
        if (!is_a($this->criteria, 'Lemon\RestBundle\Object\Criteria', true)) {
            throw new \RuntimeException(sprintf("%s is not a valid criteria"));
        }

        return new $this->criteria($elements);
    }
}
