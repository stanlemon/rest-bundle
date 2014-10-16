<?php

namespace Lemon\RestBundle\Tests\Controller;

use Doctrine\ORM\AbstractQuery;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Object\Criteria\DefaultCriteria;
use Lemon\RestBundle\Tests\Fixtures\Car;
use Lemon\RestBundle\Tests\Fixtures\Tag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use Lemon\RestBundle\Tests\Fixtures\Person;

class RestControllerTest extends WebTestCase
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
        $registry->addClass('person', 'Lemon\RestBundle\Tests\Fixtures\Person');
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
    protected function makeRequest($method, $uri, $content = null, $parameters = array())
    {
        $request = Request::create(
            $uri,
            $method,
            $parameters,
            $cookies = array(),
            $files = array(),
            $server = array(
                'HTTP_ACCEPT' => 'application/json',
            ),
            $content
        );
        return $request;
    }

    public function testListAction()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person1 = new Person();
        $person1->name = "Stan Lemon";

        $person2 = new Person();
        $person2->name = "Sara Lemon";

        $person3 = new Person();
        $person3->name = "Lucy Lemon";

        $person4 = new Person();
        $person4->name = "Evelyn Lemon";

        $person5 = new Person();
        $person5->name = "Henry Lemon";

        $this->em->persist($person1);
        $this->em->persist($person2);
        $this->em->persist($person3);
        $this->em->persist($person4);
        $this->em->persist($person5);
        $this->em->flush();
        $this->em->clear();

        $parameters = array(
            DefaultCriteria::OFFSET => 1,
            DefaultCriteria::LIMIT => 3,
            DefaultCriteria::ORDER_BY => 'name'
        );

        $request = $this->makeRequest('GET', '/person', null, $parameters);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->listAction($request, 'person');

        $data = json_decode($response->getContent());

        $this->assertCount(3, $data->results);
        $this->assertEquals($person5->id, $data->results[0]->id);
        $this->assertEquals($person3->id, $data->results[1]->id);
        $this->assertEquals($person2->id, $data->results[2]->id);
    }

    public function testGetAction()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->ssn = '123-45-678';

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest('GET', '/person/' . $person->id);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->getAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $this->assertTrue(!isset($data->created), "Excluded fields should not appear");

        $this->assertEquals($person->id, $data->id);
        $this->assertEquals($person->name, $data->name);
        $this->assertEquals($person->ssn, $data->ssn, "Our read-only property is still readable");
    }

    public function testGetActionForNonExistentObject()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $id = mt_rand(1,100);

        $request = $this->makeRequest('GET', '/person/' . $id);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->getAction($request, 'person', $id);

        $data = json_decode($response->getContent());

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertObjectHasAttribute('code', $data);
        $this->assertObjectHasAttribute('message', $data);
        $this->assertEquals(404, $data->code);
    }

    public function testPostAction()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => 'Stan Lemon', 'created' => date('Y-m-d H:i:s')))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $data = json_decode($response->getContent());

        $this->assertEquals($data->name, "Stan Lemon");

        $this->em->clear();

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertNull($refresh->created, "Excluded properties, even when passed should not be set");
    }

    public function testPutAction()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->created = new \DateTime("-1 day");
        $person->updated = new \DateTime("-12 hours");

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'id' => $person->id,
                'name' => $person->name,
                'created' => date('Y-m-d H:i:s'),
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $this->assertEquals($person->id, $data->id);
        $this->assertEquals($person->name, $data->name);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertEquals($person->created, $refresh->created, "Excluded fields do not get updated when passed in");
        $this->assertEquals($person->updated, $refresh->updated, "Excluded fields not get updated when not passed in");
    }

    public function testDeleteAction()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest('DELETE', '/person/1');

        $person = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($person);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->deleteAction($request, 'person', 1);

        $this->assertEquals("null", $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());

        $person = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNull($person);
    }

    public function testPutActionWithoutIdInPayload()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array('name' => $person->name))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $this->assertEquals($person->id, $data->id);
        $this->assertEquals($person->name, $data->name);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
    }

    public function testPostActionWithNestedCollection()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $tag = new Tag();
        $tag->name = 'baz';

        $this->em->persist($tag);
        $this->em->flush($tag);
        $this->em->clear();

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array(
                'name' => 'Stan Lemon',
                'cars' => array(
                    array(
                        'name' => 'Honda',
                        'year' => 2006,
                    )
                ),
                'tags' => array(
                    array('name' => 'foo'),
                    array('name' => 'bar'),
                    array('id' => $tag->id, 'name' => 'baz'),
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $this->em->clear();

        $data = json_decode($response->getContent());

        $this->assertEquals($data->name, "Stan Lemon");

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertCount(1, $refresh->cars);
        $this->assertEquals("Honda", $refresh->cars[0]->name);
        $this->assertEquals(2006, $refresh->cars[0]->year);
        $this->assertCount(3, $refresh->tags);

        $foundExisting = false;

        foreach ($refresh->tags as $t) {
            if ($t->id == $tag->id) {
                $foundExisting = true;
            }
        }

        $this->assertTrue($foundExisting, "Found existing tag on refreshed entity");
    }

    public function testPostActionWithNestedCollectionAndId0()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array(
                'id' => 0,
                'name' => 'Stan Lemon',
                'cars' => array(
                    array(
                        'id' => 0,
                        'name' => 'Honda',
                        'year' => 2006,
                    )
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $data = json_decode($response->getContent());

        $this->assertEquals($data->name, "Stan Lemon");

        $this->em->clear();

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertNotEquals(0, $refresh->id);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertCount(1, $refresh->cars);
        $this->assertNotEquals(0, $refresh->cars[0]->id);
        $this->assertEquals("Honda", $refresh->cars[0]->name);
        $this->assertEquals(2006, $refresh->cars[0]->year);
        $this->assertCount(1, $refresh->cars);
    }

    public function testPostActionWithNestedEntity()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array(
                'name' => 'Stan Lemon',
                'mother' => array(
                    'name' => 'Sharon Lemon'
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $this->em->clear();

        $data = json_decode($response->getContent());

        $this->assertEquals($data->name, "Stan Lemon");

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals("Sharon Lemon", $refresh->mother->name);
    }

    public function testPutActionWithNestedCollectionAndExistingItem()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $created = new \DateTime();
        $created->modify("-1 month");

        $car = new Car();
        $car->name = 'Honda';
        $car->year = 2006;
        $car->created = $created;

        $tag = new Tag();
        $tag->name = 'foo';

        $person = new Person();
        $person->name = "Stan Lemon";

        $car->person = $person;

        $person->cars[] = $car;
        $person->tags[] = $tag;

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Car')->findOneBy(array(
            'id' => $car->id
        ));

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'cars' => array(
                    array(
                        'id' => $car->id,
                        'name' => "Honda Odyssey",
                        'year' => 2006
                    )
                ),
                'tags' => array(
                    array(
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ),
                    array(
                        'name' => 'bar',
                    ),
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(1, $refresh->cars);
        $this->assertEquals("Honda Odyssey", $refresh->cars[0]->name);
        $this->assertEquals(2006, $refresh->cars[0]->year);
        $this->assertNotNull($refresh->cars[0]->created);
        $this->assertEquals($created->format('U'), $refresh->cars[0]->created->format('U'));
        $this->assertCount(2, $refresh->tags);
        $this->assertEquals($tag->id, $refresh->tags[0]->id);
        $this->assertEquals($tag->name, $refresh->tags[0]->name);
    }

    public function testPutActionWithNestedCollectionAndExistingItemAndNewItem()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $car = new Car();
        $car->name = 'Honda';
        $car->year = 2006;

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->cars[] = $car;

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'cars' => array(
                    array(
                        'id' => $car->id,
                        'name' => "Honda Odyssey",
                        'year' => 2006,
                    ),
                    array(
                        'name' => "Mercedes Benz 300c",
                        'year' => 2013,
                    )
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(2, $refresh->cars);
        $this->assertEquals("Honda Odyssey", $refresh->cars[0]->name);
        $this->assertEquals(2006, $refresh->cars[0]->year);
        $this->assertEquals("Mercedes Benz 300c", $refresh->cars[1]->name);
        $this->assertEquals(2013, $refresh->cars[1]->year);
    }

    public function testPutActionWithNestedCollectionAndNewItemWithId0()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'cars' => array(
                    array(
                        'id' => 0,
                        'name' => "Honda Odyssey",
                        'year' => 2006,
                    )
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(1, $refresh->cars);
        $this->assertNotEquals(0, $refresh->cars[0]->id);
        $this->assertEquals("Honda Odyssey", $refresh->cars[0]->name);
        $this->assertEquals(2006, $refresh->cars[0]->year);
    }

    public function testPutActionWithNestedCollectionAndRemoveExistingItem()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $tag1 = new Tag();
        $tag1->name = 'foo';

        $tag2 = new Tag();
        $tag2->name = 'bar';

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->tags[] = $tag1;
        $person->tags[] = $tag2;

        $car1 = new Car();
        $car1->name = 'Honda';
        $car1->year = 2006;
        $car1->person = $person;

        $person->cars->add($car1);

        $car2 = new Car();
        $car2->name = 'Mercedes Benz';
        $car2->year = 2013;
        $car2->person = $person;

        $person->cars->add($car2);

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertCount(2, $refresh->cars);

        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'cars' => array(
                    array(
                        'id' => $car1->id,
                        'name' => "Honda Odyssey",
                        'year' => 2006,
                    )
                ),
                'tags' => array(
                    array(
                        'id' => $tag2->id,
                        'name' => $tag2->name
                    )
                )
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(1, $refresh->cars);
        $this->assertEquals("Honda Odyssey", $refresh->cars[0]->name);
        $this->assertEquals(2006, $refresh->cars[0]->year);
        $this->assertCount(1, $refresh->tags);
        $this->assertEquals($tag2->id, $refresh->tags[0]->id);
        $this->assertEquals($tag2->name, $refresh->tags[0]->name);
    }

    public function testPutActionWithNestedCollectionAndRemoveAllExistingItems()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";

        $car1 = new Car();
        $car1->name = 'Honda';
        $car1->year = 2006;
        $car1->person = $person;

        $person->cars->add($car1);

        $car2 = new Car();
        $car2->name = 'Mercedes Benz';
        $car2->year = 2013;
        $car2->person = $person;

        $person->cars->add($car2);

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertCount(2, $refresh->cars);

        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'cars' => array(),
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', 1);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(0, $refresh->cars);
    }

    public function testPutActionWithNestedEntity()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $mother = new Person();
        $mother->name = "Sharon Lemon";

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->mother = $mother;

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'mother' => array(
                    'id' => $mother->id,
                    'name' => $mother->name,
                )
            ))
        );

        $controller->putAction($request, 'person', 1);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals($person->mother->id, $refresh->mother->id);
        $this->assertEquals($person->mother->name, $refresh->mother->name);
    }

    public function testPutActionWithNestedEntityThatHasANestedCollection()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $car1 = new Car();
        $car1->name = "Chrysler Caravan";
        $car1->year = 2003;

        $car2 = new Car();
        $car2->name = "Suzuki Sidekick";
        $car2->year = 1999;

        $mother = new Person();
        $mother->name = "Sharon Lemon";
        $mother->cars->add($car1);
        $mother->cars->add($car2);

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->mother = $mother;

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'mother' => array(
                    'id' => $mother->id,
                    'name' => $mother->name,
                    'mother' => array(
                        'name' => 'Arbutus',
                    ),
                    'cars' => array(
                        array(
                            'id' => $car1->id,
                            'name' => $car1->name,
                            'year' => $car1->year,
                        ),
                        array(
                            'name' => 'Ford Fusion',
                            'year' => 2013,
                        ),
                    ),
                )
            ))
        );

        $controller->putAction($request, 'person', 1);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals($person->mother->id, $refresh->mother->id);
        $this->assertEquals($person->mother->name, $refresh->mother->name);
        $this->assertNotNull($refresh->mother->mother);
        $this->assertCount(2, $refresh->mother->cars);
        $this->assertEquals($car1->name, $refresh->mother->cars[0]->name);
        $this->assertEquals($car1->year, $refresh->mother->cars[0]->year);
        $this->assertEquals("Ford Fusion", $refresh->mother->cars[1]->name);
        $this->assertEquals(2013, $refresh->mother->cars[1]->year);
    }

    public function testPutActionWithNestedEntityRemoved()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $mother = new Person();
        $mother->name = "Sharon Lemon";

        $person = new Person();
        $person->name = "Stan Lemon";
        $person->mother = $mother;

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
            ))
        );

        $controller->putAction($request, 'person', 1);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertNull($refresh->mother);

        $refreshMother = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $mother->id
        ));

        $this->assertNotNull($refreshMother, "We removed the relationship, but not the entity");
    }

    public function testPostActionWithInvalidAttribute()
    {
        $query = $this->em->createQuery("SELECT COUNT(p.id) FROM Lemon\RestBundle\Tests\Fixtures\Person p");
        $total = $query->execute(array(), AbstractQuery::HYDRATE_SINGLE_SCALAR);

        $controller = $this->container->get('lemon_rest.controller');

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => ''))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $this->em->clear();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            $total,
            $this->em->createQuery("SELECT COUNT(p.id) FROM Lemon\RestBundle\Tests\Fixtures\Person p")
                ->execute(array(), AbstractQuery::HYDRATE_SINGLE_SCALAR)
        );
    }

    public function testPutActionWithInvalidAttribute()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person();
        $person->name = "Stan Lemon";

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'POST',
            '/person/' . $person->id,
            json_encode(array('name' => ''))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->putAction($request, 'person', $person->id);

        $this->assertEquals(400, $response->getStatusCode());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->name, $refresh->name);
    }

    public function test500()
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher$eventDispatcher */
        $eventDispatcher = $this->container->get('lemon_rest.event_dispatcher');
        $eventDispatcher->addListener(RestEvents::PRE_CREATE, function(){
            throw new \RuntimeException("Proceed no further!");
        });

        $controller = $this->container->get('lemon_rest.controller');

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => 'Stan Lemon', 'created' => date('Y-m-d H:i:s')))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $this->assertEquals(500, $response->getStatusCode());

        $data = json_decode($response->getContent());

        $this->assertEquals("Proceed no further!", $data->message);
    }

    public function testHttpException()
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher$eventDispatcher */
        $eventDispatcher = $this->container->get('lemon_rest.event_dispatcher');
        $eventDispatcher->addListener(RestEvents::PRE_CREATE, function(){
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Bad Request");
        });

        $controller = $this->container->get('lemon_rest.controller');

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => 'Stan Lemon', 'created' => date('Y-m-d H:i:s')))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $controller->postAction($request, 'person');

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent());

        $this->assertEquals("Bad Request", $data->message);
    }

    public function testPatchAction()
    {
        $controller = $this->container->get('lemon_rest.controller');

        $person = new Person;
        $person->name = 'Stan Lemon';
        $person->ssn = '123-45-678';
        $person->favoriteColor = 'purple';

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest(
            'PATCH',
            '/person/' . $person->id,
            json_encode(array(
                'id' => $person->id,
                'favorite_color' => 'blue',
            ))
        );

        $controller->patchAction($request, 'person', $person->id);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertEquals('blue', $refresh->favoriteColor);
        $this->assertEquals($person->ssn, $refresh->ssn);
        $this->assertEquals($person->name, $refresh->name);
    }
}
