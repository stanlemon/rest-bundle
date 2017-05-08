<?php
namespace Lemon\RestBundle\Controller;

use Lemon\RestBundle\Object\Criteria\CriteriaFactory;
use Lemon\RestBundle\Object\IdHelper;
use Lemon\RestBundle\Object\ManagerInterface;
use Lemon\RestBundle\Request\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ResourceController
{
    /**
     * @var Handler
     */
    protected $handler;
    /**
     * @var Response
     */
    protected $response;
    /**
     * @var CriteriaFactory
     */
    protected $criteriaFactory;
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param Handler $handler
     * @param CriteriaFactory $criteriaFactory
     * @param RouterInterface $router
     */
    public function __construct(
        Handler $handler,
        CriteriaFactory $criteriaFactory,
        RouterInterface $router
    ) {
        $this->handler = $handler;
        $this->criteriaFactory = $criteriaFactory;
        $this->router = $router;
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

        $criteria = $this->criteriaFactory->create($request->query->all());

        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (ManagerInterface $manager) use ($response, $criteria) {
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
            function (ManagerInterface $manager) use ($id) {
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
        $response = $this->response;
        $router = $this->router;

        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (ManagerInterface $manager, $object) use ($response, $resource, $router) {
                $manager->create($object);

                $response->setStatusCode(201);
                $response->headers->set('Location', $router->generate(
                    'lemon_rest_get',
                    array(
                        'resource' => $resource,
                        'id' => IdHelper::getId($object),
                    ),
                    RouterInterface::ABSOLUTE_URL
                ));

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
    public function putAction(Request $request, $resource, $id)
    {
        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (ManagerInterface $manager, $object) {
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
    public function patchAction(Request $request, $resource, $id)
    {
        return $this->handler->handle(
            $request,
            $this->response,
            $resource,
            function (ManagerInterface $manager, $object) {
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
            function (ManagerInterface $manager) use ($response, $id) {
                $response->setStatusCode(204);

                $manager->delete($id);
            }
        );
    }

    public function optionsAction(Request $request, $resource, $id = null)
    {
        return $this->handler->options(
            $request,
            $this->response,
            $resource,
            $id
        );
    }
}
