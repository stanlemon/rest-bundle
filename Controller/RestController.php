<?php
namespace Lemon\RestBundle\Controller;

use Lemon\RestBundle\Object\Criteria;
use Lemon\RestBundle\Object\Manager;
use Lemon\RestBundle\Request\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestController
{
    /**
     * @var Handler
     */
    protected $handler;
    /**
     * @var Criteria
     */
    protected $criteria;
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param Handler $handler
     */
    public function __construct(
        Handler $handler,
        Criteria $criteria
    ) {
        $this->handler = $handler;
        $this->criteria = $criteria;
        $this->response = new Response();
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
                $this->criteria->init($request->query->all());
                
                $results = $manager->search($this->criteria);

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
