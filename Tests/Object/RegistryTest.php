<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\Registry;
use Lemon\RestBundle\Object\Definition;

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
        $definition = new Definition('person', '\Lemon\RestBundle\Tests\Fixtures\Person');

        $registry = new Registry();
        $registry->add($definition);

        $this->assertTrue($registry->has('person'));
        $this->assertEquals(
            '\Lemon\RestBundle\Tests\Fixtures\Person',
            $registry->get('person')->getClass()
        );
        $this->assertEquals(
            array(
                'person' => $definition
            ),
            $registry->all()
        );
    }

    /**
     * @covers ::addClass()
     */
    public function testAddClassDoesNotExist()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $registry = new Registry();
        $registry->add(new Definition('foo', '\foo\bar'));
    }

    /**
     * @covers ::getClass()
     */
    public function testGetClassNotInRegistry()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $registry = new Registry();
        $registry->get('\foo\bar');
    }
}
