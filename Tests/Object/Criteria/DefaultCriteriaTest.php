<?php
namespace Lemon\RestBundle\Tests\Object\Criteria;

use Lemon\RestBundle\Object\Criteria\DefaultCriteria;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\Criteria\DefaultCriteria
 */
class DefaultCriteriaTest extends \PHPUnit_Framework_TestCase
{
    public function testCriteria()
    {
        $data = array(
            DefaultCriteria::ORDER_BY => 'firstName',
            DefaultCriteria::ORDER_DIR => DefaultCriteria::ORDER_DIR_DESC,
            DefaultCriteria::LIMIT => 5,
            DefaultCriteria::OFFSET => 1,
            'foo' => 'bar'
        );

        $criteria = new DefaultCriteria($data);

        $this->assertEquals(array('foo' => 'bar'), $criteria->toArray());
        $this->assertEquals('firstName', $criteria->getOrderBy());
        $this->assertEquals(DefaultCriteria::ORDER_DIR_DESC, $criteria->getOrderDir());
        $this->assertEquals(5, $criteria->getLimit());
        $this->assertEquals(1, $criteria->getOffset());
    }

    public function testCriteriaDefaults()
    {
        $data = array(
            'foo' => 'bar',
            'hello' => 'world',
        );

        $criteria = new DefaultCriteria($data);

        $this->assertEquals($data, $criteria->toArray());
        $this->assertNull($criteria->getOrderBy());
        $this->assertNotNull($criteria->getOrderDir());
        $this->assertEquals(DefaultCriteria::DEFAULT_LIMIT, $criteria->getLimit());
        $this->assertEquals(DefaultCriteria::DEFAULT_OFFSET, $criteria->getOffset());
    }

    public function testCriteriaOrderDir()
    {
        $data = array(
            DefaultCriteria::ORDER_BY => 'foo',
            DefaultCriteria::ORDER_DIR => DefaultCriteria::ORDER_DIR_ASC,
        );

        $criteria = new DefaultCriteria($data);

        $this->assertNotNull($criteria->getOrderBy());
        $this->assertEquals('foo', $criteria->getOrderBy());
        $this->assertEquals(DefaultCriteria::ORDER_DIR_ASC, $criteria->getOrderDir());

        $data = array(
            DefaultCriteria::ORDER_BY => 'foo',
            DefaultCriteria::ORDER_DIR => DefaultCriteria::ORDER_DIR_DESC,
        );

        $criteria = new DefaultCriteria($data);

        $this->assertNotNull($criteria->getOrderBy());
        $this->assertEquals('foo', $criteria->getOrderBy());
        $this->assertEquals(DefaultCriteria::ORDER_DIR_DESC, $criteria->getOrderDir());
    }
}
