<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\ManagerFactory;
use Lemon\RestBundle\Object\Registry;
use Lemon\RestBundle\Object\Definition;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\ManagerFactory
 */
class ManagerFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCreatesManagerWithCorrectClass()
    {
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')->getMock();
        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = new Registry();
        $registry->add(new Definition('person', 'Lemon\RestBundle\Tests\Fixtures\Person'));

        $managerFactory = new ManagerFactory(
            $registry,
            $doctrine,
            $eventDispatcher
        );

        $manager = $managerFactory->create("person");

        $this->assertEquals(
            'Lemon\RestBundle\Tests\Fixtures\Person',
            $manager->getClass()
        );
    }
}
