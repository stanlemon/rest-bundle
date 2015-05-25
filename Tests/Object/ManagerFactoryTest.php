<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\ManagerFactory;
use Lemon\RestBundle\Object\Registry;
use Lemon\RestBundle\Object\Definition;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\ManagerFactory
 */
class ManagerFactoryTest extends \Xpmock\TestCase
{

    public function testCreatesManagerWithCorrectClass()
    {
        $eventDispatcher = $this->mock('Symfony\Component\EventDispatcher\EventDispatcher')->new();
        $doctrine = $this->mock('Doctrine\Bundle\DoctrineBundle\Registry')->new();

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
