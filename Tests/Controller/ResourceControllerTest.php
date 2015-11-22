<?php

namespace Lemon\RestBundle\Tests\Controller;

use Lemon\RestBundle\Object\Definition;
use Lemon\RestBundle\Tests\Fixtures\Place;
use Lemon\RestBundle\Tests\FunctionalTestCase;
use Lemon\RestBundle\Event\RestEvents;
use Lemon\RestBundle\Object\Criteria\DefaultCriteria;
use Lemon\RestBundle\Tests\Fixtures\Car;
use Lemon\RestBundle\Tests\Fixtures\Tag;
use Lemon\RestBundle\Tests\Fixtures\Person;
use Lemon\RestBundle\Tests\Fixtures\FootballTeam;

abstract class ResourceControllerTest extends FunctionalTestCase
{
    /**
     * @var \Lemon\RestBundle\Controller\ResourceController
     */
    protected $controller;

    public function setUp()
    {
        parent::setUp();

        $this->controller = $this->container->get('lemon_rest.resource_controller');
    }

    protected function assertArrayHasObjectWithValue($expected, $array, $key)
    {
        $found = false;

        foreach ($array as $value) {
            if ($value->$key === $expected) {
                $found = true;
            }
        }

        $this->assertTrue($found, sprintf("Array did not have an object whose property %s had value %s", $key, $expected));
    }

    public function testListAction()
    {
        $person1 = new Person();
        $person1->name = "Stan Lemon";
        $person1->created = new \DateTime();

        $person2 = new Person();
        $person2->name = "Sara Lemon";
        $person2->created = new \DateTime();

        $person3 = new Person();
        $person3->name = "Lucy Lemon";
        $person3->created = new \DateTime();

        $person4 = new Person();
        $person4->name = "Evelyn Lemon";
        $person4->created = new \DateTime();

        $person5 = new Person();
        $person5->name = "Henry Lemon";
        $person5->created = new \DateTime();

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
        $response = $this->controller->listAction($request, 'person');

        $data = json_decode($response->getContent());

        $this->assertCount(3, $data);
        $this->assertEquals($person5->id, $data[0]->id);
        $this->assertEquals($person3->id, $data[1]->id);
        $this->assertEquals($person2->id, $data[2]->id);
        $this->assertEquals(5, $response->headers->get("x-total-count"));
    }

    public function testGetAction()
    {
        $person = new Person();
        $person->name = "Stan Lemon";
        $person->ssn = '123-45-678';

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $request = $this->makeRequest('GET', '/person/' . $person->id);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->getAction($request, 'person', $person->id);

        $data = json_decode($response->getContent());

        $this->assertTrue(!isset($data->updated), "Excluded fields should not appear");

        $this->assertEquals($person->id, $data->id);
        $this->assertEquals($person->name, $data->name);
        $this->assertEquals($person->ssn, $data->ssn, "Our read-only property is still readable");
    }

    public function testGetActionForNonExistentObject()
    {
        $id = mt_rand(1, 100);

        $request = $this->makeRequest('GET', '/person/' . $id);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->getAction($request, 'person', $id);

        $data = json_decode($response->getContent());

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertObjectHasAttribute('code', $data);
        $this->assertObjectHasAttribute('message', $data);
        $this->assertEquals(404, $data->code);
    }

    public function testPostAction()
    {
        $created = date(DATE_ISO8601);
        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => 'Stan Lemon', 'created' => $created))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->postAction($request, 'person');

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($response->headers->get('Location'));

        $data = json_decode($response->getContent());

        $this->assertEquals($data->name, "Stan Lemon");

        $this->em->clear();

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertEquals(new \DateTime($created), $refresh->created);
        $this->assertNull($refresh->updated, "Excluded properties, even when passed should not be set");
    }

    public function testPutAction()
    {
        $person = new Person();
        $person->name = "Stan Lemon";
        $person->created = new \DateTime("-1 day");
        $person->updated = new \DateTime("-12 hours");

        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        $created = date(DATE_ISO8601);
        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'id' => $person->id,
                'name' => $person->name,
                'created' => $created,
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->putAction($request, 'person', $person->id);

        $data = json_decode($response->getContent());

        $this->assertEquals($person->id, $data->id);
        $this->assertEquals($person->name, $data->name);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertEquals(new \DateTime($created), $refresh->created);
        $this->assertEquals($person->updated, $refresh->updated, "Excluded fields not get updated when not passed in");
    }

    public function testDeleteAction()
    {
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
        $response = $this->controller->deleteAction($request, 'person', $person->id);;

        $this->assertEquals("null", $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());

        $person = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNull($person);
    }

    public function testPutActionWithoutIdInPayload()
    {
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
        $response = $this->controller->putAction($request, 'person', $person->id);

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
        $response = $this->controller->postAction($request, 'person');

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
        $response = $this->controller->postAction($request, 'person');

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
        $response = $this->controller->postAction($request, 'person');

        $this->em->clear();

        $data = json_decode($response->getContent());

        $this->assertEquals($data->name, "Stan Lemon");

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        // If I don't do this, Doctrine ORM 2.3 has null's in all the properties of mother - seems to be a bug
        // as it is not an issues with 2.4 and later
        $this->em->refresh($refresh);

        $this->assertNotNull($refresh);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals("Sharon Lemon", $refresh->mother->name);
    }

    public function testPostActionWithNestedExistingEntity()
    {
        $mother = new Person();
        $mother->name = "Sharon Lemon";

        $this->em->persist($mother);
        $this->em->flush($mother);
        $this->em->clear();

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array(
                'name' => "Stan Lemon",
                'mother' => array(
                    'id' => $mother->id,
                    'name' => $mother->name,
                )
            ))
        );

        $response = $this->controller->postAction($request, 'person');

        $data = json_decode($response->getContent());
        
        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));
            
        $this->assertNotNull($refresh);
        $this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals($mother->id, $refresh->mother->id);
        $this->assertEquals($mother->name, $refresh->mother->name);
    }

    public function testPutActionWithNestedCollectionAndExistingItem()
    {
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
        $response = $this->controller->putAction($request, 'person', $person->id);

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
        $response = $this->controller->putAction($request, 'person', $person->id);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(2, $refresh->cars);

        $this->assertArrayHasObjectWithValue("Honda Odyssey", $refresh->cars, 'name');
        $this->assertArrayHasObjectWithValue("2006", $refresh->cars, 'year');
        $this->assertArrayHasObjectWithValue("Mercedes Benz 300c", $refresh->cars, 'name');
        $this->assertArrayHasObjectWithValue("2013", $refresh->cars, 'year');
    }

    public function testPutActionWithNestedCollectionAndNewItemWithId0()
    {
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
        $response = $this->controller->putAction($request, 'person', $person->id);

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
        $response = $this->controller->putAction($request, 'person', $person->id);

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
        $response = $this->controller->putAction($request, 'person', $person->id);

        $data = json_decode($response->getContent());

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $data->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertCount(0, $refresh->cars);
    }

    public function testPutActionWithNestedNewEntity()
    {
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
                'mother' => array(
                    'name' => "Sharon Lemon",
                )
            ))
        );

        $this->controller->putAction($request, 'person', $person->id);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($refresh);
        $this->assertEquals($person->id, $refresh->id);
        $this->assertEquals($person->name, $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals("Sharon Lemon", $refresh->mother->name);
    }

    public function testPutActionWithNestedExistingEntity()
    {
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

        $this->controller->putAction($request, 'person', $person->id);

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

        $this->controller->putAction($request, 'person', $person->id);

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
        $this->assertArrayHasObjectWithValue($car1->name, $refresh->mother->cars, 'name');
        $this->assertArrayHasObjectWithValue((string) $car1->year, $refresh->mother->cars, 'year');
        $this->assertArrayHasObjectWithValue('Ford Fusion', $refresh->mother->cars, 'name');
        $this->assertArrayHasObjectWithValue('2013', $refresh->mother->cars, 'year');
    }

    public function testPutActionWithNestedEntityRemoved()
    {
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

        $this->controller->putAction($request, 'person', $person->id);

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
        $all = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findAll();
        $total = count($all);

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => ''))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->postAction($request, 'person');

        $this->em->clear();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            $total,
            count(
                $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findAll()
            )
        );
    }

    public function testPutActionWithInvalidAttribute()
    {
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
        $response = $this->controller->putAction($request, 'person', $person->id);

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
        $eventDispatcher->addListener(RestEvents::PRE_CREATE, function () {
            throw new \RuntimeException("Proceed no further!");
        });

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => 'Stan Lemon', 'created' => date(DATE_ISO8601)))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->postAction($request, 'person');

        $this->assertEquals(500, $response->getStatusCode());

        $data = json_decode($response->getContent());

        $this->assertEquals("Proceed no further!", $data->message);
    }

    public function testHttpException()
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher$eventDispatcher */
        $eventDispatcher = $this->container->get('lemon_rest.event_dispatcher');
        $eventDispatcher->addListener(RestEvents::PRE_CREATE, function () {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Bad Request");
        });

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array('name' => 'Stan Lemon', 'created' => date(DATE_ISO8601)))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->postAction($request, 'person');

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent());

        $this->assertEquals("Bad Request", $data->message);
    }

    public function testPatchAction()
    {
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

        $this->controller->patchAction($request, 'person', $person->id);

        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertEquals('blue', $refresh->favoriteColor);
        $this->assertEquals($person->ssn, $refresh->ssn);
        $this->assertEquals($person->name, $refresh->name);
    }

    public function testGetActionWithVersion()
    {
        $footballTeam = new FootballTeam;
        $footballTeam->name = 'Steelers';
        $footballTeam->conference = 'AFC';
        $footballTeam->league = 'Amercian';

        $this->em->persist($footballTeam);
        $this->em->flush($footballTeam);
        $this->em->clear();

        $request = $this->makeRequest(
            'GET',
            '/footballTeam/' . $footballTeam->id,
            null,
            array(),
            array('HTTP_ACCEPT' => 'application/json;version=0.9.2')
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->getAction($request, 'footballTeam', $footballTeam->id);

        $data = json_decode($response->getContent());

        $this->assertEquals($footballTeam->league, $data->league);
        $this->assertTrue(!isset($data->conference));

        $request = $this->makeRequest(
            'GET',
            '/footballTeam/' . $footballTeam->id,
            null,
            array(),
            array('HTTP_ACCEPT' => 'application/json;version=1.1.2')
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->getAction($request, 'footballTeam', $footballTeam->id);

        $data = json_decode($response->getContent());

        $this->assertEquals($footballTeam->conference, $data->conference);
        $this->assertTrue(!isset($data->league));
    }

    public function testPostWithIdForObject()
    {
        $mother = new Person();
        $mother->name = "Sharon Lemon";

        $this->em->persist($mother);

        $this->em->flush();
        $this->em->clear();

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array(
                'name' => "Stan Lemon",
                'mother' => $mother->id
            ))
        );

        $this->controller->postAction($request, 'person');
        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneByName("Stan Lemon");

        $this->assertNotNull($refresh);
		$this->assertEquals("Stan Lemon", $refresh->name);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals($mother->id, $refresh->mother->id);
        $this->assertEquals($mother->name, $refresh->mother->name);
    }

    public function testPutWithIdForObject()
    {
        $mother = new Person();
        $mother->name = "Sharon Lemon";

        $this->em->persist($mother);

        $person = new Person();
        $person->name = "Stan Lemon";

        $this->em->persist($person);

        $this->em->flush();
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => $person->name,
                'mother' => $mother->id
            ))
        );

        $this->controller->putAction($request, 'person', $person->id);
        $refresh = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($refresh);
        $this->assertNotNull($refresh->mother);
        $this->assertEquals($mother->id, $refresh->mother->id);
        $this->assertEquals($mother->name, $refresh->mother->name);
    }

    public function testPostWithIdsForOneToManyRelationships()
    {
        $mustang = new Car();
        $mustang->name = 'Mustang';
        $mustang->year = '2014';
        $this->em->persist($mustang);
        $this->em->flush();

        $request = $this->makeRequest(
            'POST',
            '/person',
            json_encode(array(
                'name' => 'Stan Lemon',
                'cars' => array($mustang->id)
            ))
        );

        $response = $this->controller->postAction($request, 'person');
        $this->assertTrue($response->isSuccessful());

        $person = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'name' => 'Stan Lemon'
        ));

        $this->assertNotNull($person);
        $this->assertEquals(array('Mustang'), array_map(function($car) {
            return $car->name;
        }, $person->cars->toArray()));
    }

    public function testPutWithIdsForOneToManyRelationships()
    {
        $mustang = new Car();
        $mustang->name = 'Mustang';
        $mustang->year = '2014';

        $this->em->persist($mustang);
        $this->em->flush();

        $person = new Person();
        $person->name = "Stan Lemon";

        $this->em->persist($person);

        $this->em->flush();
        $this->em->clear();

        $request = $this->makeRequest(
            'PUT',
            '/person/' . $person->id,
            json_encode(array(
                'name' => 'Stan Lemon',
                'cars' => array($mustang->id)
            ))
        );

        $response = $this->controller->putAction($request, 'person', $person->id);
        $this->assertTrue($response->isSuccessful());

        $person = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Person')->findOneBy(array(
            'id' => $person->id
        ));

        $this->assertNotNull($person);
        $this->assertEquals(array('Mustang'), array_map(function($car) {
            return $car->name;
        }, $person->cars->toArray()));
    }

    public function testPostActionWithNestedGratherThanSecondLevel()
    {
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
                        'places' => array(
                            array(
                                'name' => 'First'
                            ),
                            array(
                                'name' => 'Second'
                            )
                        )
                    )
                ),
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->postAction($request, 'person');

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

        //Error here;
        $this->assertCount(2, $refresh->cars[0]->places);
    }

    public function testInvalidMethod()
    {
        $registry = $this->container->get('lemon_rest.object_registry');

        $definition = new Definition('place', 'Lemon\RestBundle\Tests\Fixtures\Place', true, false, false, true, false);

        $registry->add($definition);

        $place1 = new Place();
        $place1->name = "Seymour";

        $this->em->persist($place1);
        $this->em->flush($place1);
        $this->em->clear();

        $place2 = new Place();
        $place2->name = "Brownstown";

        $this->em->persist($place2);
        $this->em->flush($place2);
        $this->em->clear();

        $places = $this->em->getRepository('Lemon\RestBundle\Tests\Fixtures\Place')->findAll();

        $this->assertCount(2, $places);

        $request = $this->makeRequest(
            'POST',
            '/place',
            json_encode(array(
                'name' => 'Smallville',
            ))
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->postAction($request, 'place');

        $this->assertEquals(405, $response->getStatusCode());

        $request = $this->makeRequest(
            'GET',
            '/place'
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->listAction($request, 'place');

        $this->assertEquals(2, $response->headers->get('X-Total-Count'));
    }

    public function testOptions()
    {
        $registry = $this->container->get('lemon_rest.object_registry');

        $definition = new Definition('place', 'Lemon\RestBundle\Tests\Fixtures\Place', true, false, false, true, false);

        $registry->add($definition);

        $request = $this->makeRequest(
            'OPTIONS',
            '/place'
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->optionsAction($request, 'place');

        $this->assertEquals('OPTIONS, GET', $response->headers->get('Allowed'));

        $request = $this->makeRequest(
            'OPTIONS',
            '/place/1'
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->optionsAction($request, 'place', 1);

        $this->assertEquals('OPTIONS, DELETE, GET', $response->headers->get('Allowed'));

        $request = $this->makeRequest(
            'OPTIONS',
            '/person'
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->optionsAction($request, 'person');

        $this->assertEquals('OPTIONS, POST, GET', $response->headers->get('Allowed'));

        $request = $this->makeRequest(
            'OPTIONS',
            '/person/1'
        );

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->controller->optionsAction($request, 'person', 1);

        $this->assertEquals('OPTIONS, PUT, DELETE, GET', $response->headers->get('Allowed'));
    }
}
