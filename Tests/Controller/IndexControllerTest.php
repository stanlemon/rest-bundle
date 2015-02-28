<?php

namespace Lemon\RestBundle\Tests\Controller;

use Doctrine\ORM\AbstractQuery;
use Symfony\Component\HttpFoundation\Request;
use Lemon\RestBundle\Tests\FunctionalTestCase;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Object\Criteria\DefaultCriteria;
use Lemon\RestBundle\Object\Definition;
use Lemon\RestBundle\Tests\Fixtures\Car;
use Lemon\RestBundle\Tests\Fixtures\Tag;
use Lemon\RestBundle\Tests\Fixtures\Person;
use Lemon\RestBundle\Tests\Fixtures\FootballTeam;

class IndexControllerTest extends FunctionalTestCase
{
    /**
     * @var \Lemon\RestBundle\Controller\IndexController
     */
    protected $controller;

    public function setUp()
    {
        parent::setUp();

        $this->controller = $this->container->get('lemon_rest.index_controller');
    }

    public function testIndex()
    {
        $request = $this->makeRequest('GET', '/', null);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->indexAction($request);

        $data = json_decode($response->getContent());
        
        $this->assertNotEmpty($data);
        $this->assertEquals(
            "http://localhost/person",
            $data->person_url
        );
        $this->assertEquals(
            "http://localhost/footballTeam",
            $data->footballTeam_url
        );
    }
}
