<?php
namespace Lemon\RestBundle\Controller;

use Lemon\RestBundle\Object\Registry;
use Symfony\Component\HttpFoundation\Request;
use Negotiation\FormatNegotiatorInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class IndexController
{
    /**
     * @var FormatNegotiatorInterface
     */
    protected $negotiator;
    /**
     * @var Serializer
     */
    protected $serializer;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param FormatNegotiatorInterface $negotiator
     * @param Serializer $serializer
     * @param Registry $registry
     * @param Router $router
     */
    public function __construct(
        FormatNegotiatorInterface $negotiator,
        Serializer $serializer,
        Registry $registry,
        RouterInterface $router
    )
    {
        $this->negotiator = $negotiator;
        $this->serializer = $serializer;
        $this->registry = $registry;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $format = $this->negotiator->getBestFormat(
            $request->headers->get('Accept')
        );

        $data = array();

        foreach ($this->registry->getClasses() as $name => $class) {
            $data[$name . '_url'] = $this->router->generate(
                'lemon_rest_list',
                array('resource' => $name),
                RouterInterface::ABSOLUTE_URL
            );
        }

        $output = $this->serializer->serialize($data, $format);

        $response = new Response();
        $response->headers->set('Content-Type', $request->headers->get('Accept'));
        $response->setContent($output);

        return $response;
    }
}
