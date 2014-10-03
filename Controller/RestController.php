<?php
namespace Lemon\RestBundle\Controller;

use Lemon\RestBundle\Object\Criteria;
use Lemon\RestBundle\Request\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestController
{
    protected $handler;

    public function __construct(
        Handler $handler
    ) {
        $this->handler = $handler;
    }

    public function listAction(Request $request, $resource)
    {
        $response = new Response();

        return $this->handler->handle(
            $request,
            $response,
            $resource,
            function ($manager) use ($request) {
                $criteria = new Criteria($request->query->all());

                return $manager->search($criteria);
            }
        );
    }

    public function getAction(Request $request, $resource, $id)
    {
        $response = new Response();

        return $this->handler->handle(
            $request,
            $response,
            $resource,
            function ($manager) use ($id) {
                return $manager->retrieve($id);
            }
        );
    }

    public function postAction(Request $request, $resource)
    {
        $response = new Response();

        return $this->handler->handle(
            $request,
            $response,
            $resource,
            function ($manager, $object) {
                return $manager->create($object);
            }
        );
    }

    public function putAction(Request $request, $resource, $id)
    {
        $response = new Response();

        return $this->handler->handle(
            $request,
            $response,
            $resource,
            function ($manager, $object) use ($id) {
                $reflection = new \ReflectionObject($object);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($object, $id);

                $manager->update($object);

                return $object;
            }
        );
    }

    public function deleteAction(Request $request, $resource, $id)
    {
        $response = new Response();

        return $this->handler->handle(
            $request,
            $response,
            $resource,
            function ($manager) use ($response, $id) {
                $response->setStatusCode(204);

                $manager->delete($id);
            }
        );
    }
}
