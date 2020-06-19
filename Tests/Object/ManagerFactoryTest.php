<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\ManagerFactory;
use Lemon\RestBundle\Object\Registry;
use Lemon\RestBundle\Object\Definition;
use Lemon\RestBundle\Tests\FunctionalTestCase;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\ManagerFactory
 */
class ManagerFactoryTest extends FunctionalTestCase
{

    public function testCreatesManagerWithCorrectClass()
    {
        $registry = new Registry();
        $registry->add(new Definition('person', 'Lemon\RestBundle\Tests\Fixtures\Person'));

        $managerFactory = new ManagerFactory(
            $registry,
            $this->container->get('lemon_doctrine'),
            $this->container->get('lemon_rest.event_dispatcher')
        );

        $manager = $managerFactory->create("person");

        $this->assertEquals(
            'Lemon\RestBundle\Tests\Fixtures\Person',
            $manager->getClass()
        );
    }
}
