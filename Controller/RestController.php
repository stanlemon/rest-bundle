<?php
namespace Lemon\RestBundle\Controller;

use JMS\Serializer\Construction\DoctrineObjectConstructor;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use Lemon\RestBundle\Object\Criteria;
use Lemon\RestBundle\Object\Manager;
use Lemon\RestBundle\Request\Handler;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestController
{
    /**
     * @var Handler
     */
    protected $handler;
    /**
     * @var Response
     */
    protected $response;

    protected $container;

    /**
     * @param Handler $handler
     */
    public function __construct(
        Handler $handler,
        Container $container
    ) {
        $this->handler = $handler;
        $this->response = new Response();
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param string $resource
     * @return Response
     */
    public function listAction(Request $request, $resource)
    {
        $response = $this->response;

        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (Manager $manager) use ($response, $request) {
                $criteria = new Criteria($request->query->all());

                $results = $manager->search($criteria);

                $response->headers->set('X-Total-Count', $results->getTotal());

                return $results;
            }
        );
    }

    /**
     * @param Request $request
     * @param string $resource
     * @param int $id
     * @return Response
     */
    public function getAction(Request $request, $resource, $id)
    {
        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (Manager $manager) use ($id) {
                return $manager->retrieve($id);
            }
        );
    }

    /**
     * @param Request $request
     * @param string $resource
     * @return Response
     */
    public function postAction(Request $request, $resource)
    {
        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (Manager $manager, $object) {
                return $manager->create($object);
            }
        );
    }

    /**
     * @param Request $request
     * @param string $resource
     * @param int $id
     * @return Response
     */
    public function putAction(Request $request, $resource, $id)
    {
        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (Manager $manager, $object) use ($id) {
                $reflection = new \ReflectionObject($object);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($object, $id);

                $manager->update($object);

                return $object;
            }
        );
    }

    /**
     * @param Request $request
     * @param string $resource
     * @param int $id
     * @return Response
     */
    public function patchAction(Request $request, $resource, $id)
    {
        $class = $this->container->get('lemon_rest.object_registry')->getClass($resource);

        $doctrineConstructor = new DoctrineObjectConstructor(
            $this->container->get('doctrine'),
            new UnserializeObjectConstructor()
        );

        $serializer = $this->container->get('jms_serializer');

        $serializerReflection = new \ReflectionObject($serializer);

        $navigatorProperty = $serializerReflection->getProperty('navigator');
        $navigatorProperty->setAccessible(true);

        $navigator = $navigatorProperty->getValue($serializer);

        $navigatorReflection = new \ReflectionObject($navigator);
        $objectConstructorProperty = $navigatorReflection->getProperty('objectConstructor');
        $objectConstructorProperty->setAccessible(true);

        $oldConstructor = $objectConstructorProperty->getValue($navigator);

        $objectConstructorProperty->setValue($navigator, $doctrineConstructor);

        $object = $serializer->deserialize(
            $request->getContent(),
            $class,
            'json'
        );

        $objectConstructorProperty->setValue($oldConstructor, $doctrineConstructor);

        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (Manager $manager) use ($id, $object) {
                $reflection = new \ReflectionObject($object);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($object, $id);

                $manager->partialUpdate($object);

                return $object;
            }
        );
    }

    /**
     * @param Request $request
     * @param string $resource
     * @param int $id
     * @return Response
     */
    public function deleteAction(Request $request, $resource, $id)
    {
        $response = $this->response;

        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (Manager $manager) use ($response, $id) {
                $response->setStatusCode(204);

                $manager->delete($id);
            }
        );
    }
}
