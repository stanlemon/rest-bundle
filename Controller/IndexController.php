<?php
namespace Lemon\RestBundle\Controller;

use Lemon\RestBundle\Object\Registry;
use Symfony\Component\HttpFoundation\Request;
use Negotiation\Negotiator;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class IndexController
{
    /**
     * @var Negotiator
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
     * @param Negotiator $negotiator
     * @param Serializer $serializer
     * @param Registry $registry
     * @param RouterInterface $router
     */
    public function __construct(
        Negotiator $negotiator,
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
        $accept = $this->negotiator->getBest(
            $request->headers->get('Accept'), 
            ['application/json', 'text/html', 'application/xml']
        );

        $value = $accept !== null ? $accept->getValue() : "application/json";
        $format = substr($value, strpos($value, "/") + 1);

        if (empty($format) || $format == 'html') {
            $format = 'json';
        }

        $data = array();

        foreach ($this->registry->all() as $definition) {
            $data[$definition->getName() . '_url'] = $this->router->generate(
                'lemon_rest_list',
                array('resource' => $definition->getName()),
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
