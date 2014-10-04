<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\Criteria;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\Criteria
 */
class CriteriaTest extends \PHPUnit_Framework_TestCase
{
    public function testCriteria()
    {
        $data = array(
            Criteria::ORDER_BY => 'firstName',
            Criteria::ORDER_DIR => Criteria::ORDER_DIR_DESC,
            Criteria::LIMIT => 5,
            Criteria::OFFSET => 1,
            'foo' => 'bar'
        );

        $criteria = new Criteria($data);

        $this->assertEquals(array('foo' => 'bar'), $criteria->toArray());
        $this->assertEquals(
            array('firstName' => Criteria::ORDER_DIR_DESC),
            $criteria->getOrderBy()
        );
        $this->assertEquals(5, $criteria->getLimit());
        $this->assertEquals(1, $criteria->getOffset());
    }

    public function testCriteriaDefaults()
    {
        $data = array(
            'foo' => 'bar',
            'hello' => 'world',
        );

        $criteria = new Criteria($data);

        $this->assertEquals($data, $criteria->toArray());
        $this->assertNull($criteria->getOrderBy());
        $this->assertEquals(Criteria::DEFAULT_LIMIT, $criteria->getLimit());
        $this->assertEquals(Criteria::DEFAULT_OFFSET, $criteria->getOffset());
    }
}
