<?php
namespace Lemon\RestBundle\Controller;

use Lemon\RestBundle\Object\Criteria;
use Lemon\RestBundle\Object\ManagerFactory;
use Lemon\RestBundle\Request\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestController
{
    protected $managerFactory;
    protected $handler;

    public function __construct(
        ManagerFactory $managerFactory,
        Handler $handler
    ) {
        $this->managerFactory = $managerFactory;
        $this->handler = $handler;
    }

    public function listAction(Request $request, $resource)
    {
        $response = new Response();

        $manager = $this->managerFactory->create($resource);

        return $this->handler->handle(
            $request,
            $response,
            $manager->getClass(),
            function () use ($manager, $request) {
                $criteria = new Criteria($request->query->all());

                return $manager->search($criteria);
            }
        );
    }

    public function getAction(Request $request, $resource, $id)
    {
        $response = new Response();

        $manager = $this->managerFactory->create($resource);

        return $this->handler->handle(
            $request,
            $response,
            $manager->getClass(),
            function () use ($manager, $id) {
                return $manager->retrieve($id);
            }
        );
    }

    public function postAction(Request $request, $resource)
    {
        $response = new Response();

        $manager = $this->managerFactory->create($resource);

        return $this->handler->handle(
            $request,
            $response,
            $manager->getClass(),
            function ($object) use ($manager) {
                return $manager->create($object);
            }
        );
    }

    public function putAction(Request $request, $resource, $id)
    {
        $response = new Response();

        $manager = $this->managerFactory->create($resource);

        return $this->handler->handle(
            $request,
            $response,
            $manager->getClass(),
            function ($object) use ($manager, $id) {
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

        $manager = $this->managerFactory->create($resource);

        return $this->handler->handle(
            $request,
            $response,
            $manager->getClass(),
            function () use ($response, $manager, $id) {
                $response->setStatusCode(204);

                $manager->delete($id);
            }
        );
    }
}
