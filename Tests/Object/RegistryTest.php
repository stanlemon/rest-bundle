<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\Registry;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\Registry
 */
class RegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::addClass()
     * @covers ::hasClass()
     * @covers ::getClass()
     * @covers ::getClasses()
     */
    public function testAddClass()
    {
        $registry = new Registry();
        $registry->addClass('person', '\Lemon\RestBundle\Tests\Fixtures\Person');

        $this->assertTrue($registry->hasClass('person'));
        $this->assertEquals(
            '\Lemon\RestBundle\Tests\Fixtures\Person',
            $registry->getClass('person')
        );
        $this->assertEquals(
            array(
                'person' => '\Lemon\RestBundle\Tests\Fixtures\Person'
            ),
            $registry->getClasses()
        );
    }

    /**
     * @covers ::addClass()
     */
    public function testAddClassDoesNotExist()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $registry = new Registry();
        $registry->addClass('foo', '\foo\bar');
    }

    /**
     * @covers ::getClass()
     */
    public function testGetClassNotInRegistry()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $registry = new Registry();
        $registry->getClass('\foo\bar');
    }
}
