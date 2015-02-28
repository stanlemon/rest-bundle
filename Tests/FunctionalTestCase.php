<?php

namespace Lemon\RestBundle\Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use Lemon\RestBundle\Object\Definition;

abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    /**
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
        $this->doctrine = $this->container->get('doctrine');
        $this->em = $this->doctrine->getManager();
        $this->serializer = $this->container->get('jms_serializer');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->doctrine->getConnection()->beginTransaction();

        $registry = $this->container->get('lemon_rest.object_registry');
        $registry->add(new Definition('person', 'Lemon\RestBundle\Tests\Fixtures\Person'));
        $registry->add(new Definition('footballTeam', 'Lemon\RestBundle\Tests\Fixtures\FootballTeam'));
    }

    protected static function getKernelClass()
    {
        return 'Lemon\RestBundle\Tests\TestKernel';
    }

    public function tearDown()
    {
        $this->doctrine->getConnection()->rollback();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string|null $content
     * @return Request
     */
    protected function makeRequest($method, $uri, $content = null, $parameters = array(), $server = array())
    {
        $request = Request::create(
            $uri,
            $method,
            $parameters,
            $cookies = array(),
            $files = array(),
            $server = array_merge(array(
                'HTTP_ACCEPT' => 'application/json',
            ), $server),
            $content
        );
        return $request;
    }
}
