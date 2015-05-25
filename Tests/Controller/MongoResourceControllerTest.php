<?php

namespace Lemon\RestBundle\Tests\Controller;

use Lemon\RestBundle\Tests\FunctionalTestCase;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Object\Criteria\DefaultCriteria;
use Lemon\RestBundle\Object\Definition;
use Lemon\RestBundle\Tests\Fixtures\Car;
use Lemon\RestBundle\Tests\Fixtures\Tag;
use Lemon\RestBundle\Tests\Fixtures\Person;
use Lemon\RestBundle\Tests\Fixtures\FootballTeam;

class MongoResourceControllerTest extends ResourceControllerTest
{

    public function setUp()
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped("MongoDB extension is not available for this test.");
        }

        $class = static::getKernelClass();

        $kernel = new $class('test_mongodb', true);
        $kernel->boot();

        $this->client = $kernel->getContainer()->get('test.client');
        $this->container = $this->client->getContainer();
        $this->doctrine = $this->container->get('doctrine_mongodb');

        try {
            $this->doctrine->getConnection()->listDatabases();
        } catch (\MongoConnectionException $e) {
            $this->markTestSkipped("MongoDB connection is not available for this test");
            return;
        }

        $this->em = $this->doctrine->getManager();
        $this->serializer = $this->container->get('jms_serializer');

        $registry = $this->container->get('lemon_rest.object_registry');
        $registry->add(new Definition('person', 'Lemon\RestBundle\Tests\Fixtures\Person'));
        $registry->add(new Definition('footballTeam', 'Lemon\RestBundle\Tests\Fixtures\FootballTeam'));

        $this->controller = $this->container->get('lemon_rest.resource_controller');
    }

    public function tearDown()
    {
        $qb = $this->doctrine->getManager()->createQueryBuilder('Lemon\RestBundle\Tests\Fixtures\Person');
        $qb->remove()
            ->getQuery()
            ->execute();

        $qb = $this->doctrine->getManager()->createQueryBuilder('Lemon\RestBundle\Tests\Fixtures\Car');
        $qb->remove()
            ->getQuery()
            ->execute();

        $qb = $this->doctrine->getManager()->createQueryBuilder('Lemon\RestBundle\Tests\Fixtures\Place');
        $qb->remove()
            ->getQuery()
            ->execute();

        $qb = $this->doctrine->getManager()->createQueryBuilder('Lemon\RestBundle\Tests\Fixtures\Tag');
        $qb->remove()
            ->getQuery()
            ->execute();

        $qb = $this->doctrine->getManager()->createQueryBuilder('Lemon\RestBundle\Tests\Fixtures\FootballTeam');
        $qb->remove()
            ->getQuery()
            ->execute();
    }
}
