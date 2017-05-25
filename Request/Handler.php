<?php

namespace Lemon\RestBundle\Request;

use Lemon\RestBundle\Object\Exception\InvalidException;
use Lemon\RestBundle\Object\Exception\NotFoundException;
use Lemon\RestBundle\Object\Exception\UnsupportedMethodException;
use Lemon\RestBundle\Object\ManagerFactoryInterface;
use Lemon\RestBundle\Object\Envelope\EnvelopeFactory;
use Lemon\RestBundle\Serializer\ConstructorFactory;
use Lemon\RestBundle\Serializer\DeserializationContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializationContext;
use Negotiation\FormatNegotiator;

class Handler
{
    /**
     * @var \Lemon\RestBundle\Object\ManagerFactoryInterface
     */
    protected $managerFactory;
    /**
     * @var \Lemon\RestBundle\Object\Envelope\EnvelopeFactory
     */
    protected $envelopeFactory;
    /**
     * @var ConstructorFactory
     */
    protected $serializer;

    /**
     * @var \Negotiation\FormatNegotiator
     */
    protected $negotiator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerFactoryInterface $managerFactory
     * @param EnvelopeFactory $envelopeFactory
     * @param ConstructorFactory $serializer
     * @param FormatNegotiator $negotiator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerFactoryInterface $managerFactory,
        EnvelopeFactory $envelopeFactory,
        ConstructorFactory $serializer,
        FormatNegotiator $negotiator,
        LoggerInterface $logger = null
    ) {
        $this->managerFactory = $managerFactory;
        $this->envelopeFactory = $envelopeFactory;
        $this->serializer = $serializer;
        $this->negotiator = $negotiator;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $resource
     * @param \Closure $callback
     * @return Response
     */
    public function handle(Request $request, Response $response, $resource, $callback)
    {
        $accept = $this->negotiator->getBest($request->headers->get('Accept'));

        $format = $this->negotiator->getFormat($accept->getValue());

        if ($format == 'html') {
            $format = 'json';
        }

        $response->headers->set('Content-Type', $accept->getValue());

        try {
            $manager = $this->managerFactory->create($resource);

            $context = new DeserializationContext();
            $context->enableMaxDepthChecks();

            $object = null;
            $content = $request->getContent();

            if (!empty($content)) {
                $object = $this
                    ->serializer
                    ->create('doctrine')
                    ->deserialize(
                        $request->getContent(),
                        $manager->getClass(),
                        $format,
                        $context
                    )
                ;
            }

            $data = $this->envelopeFactory->create(
                $callback($manager, $object)
            )->export();
        } catch (InvalidException $e) {
            $response->setStatusCode(400);
            $data = array(
                "code" => 400,
                "message" => $e->getMessage(),
                "errors" => $e->getErrors(),
            );
        } catch (NotFoundException $e) {
            $response->setStatusCode(404);
            $data = array(
                "code" => 404,
                "message" => $e->getMessage(),
            );
        } catch (UnsupportedMethodException $e) {
            $response->setStatusCode(405);
            $data = array(
                "code" => 405,
                "message" => $e->getMessage(),
            );
        } catch (HttpException $e) {
            $response->setStatusCode($e->getStatusCode());
            $data = array(
                "code" => $e->getStatusCode(),
                "message" => $e->getMessage(),
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);

            $response->setStatusCode(500);
            $data = array(
                "code" => 500,
                "message" => $e->getMessage()
            );
        }

        $groups = array(
            'Default',
            'lemon_rest',
            'lemon_rest_' . $resource,
            'lemon_rest_' . $resource . '_' . strtolower($request->getMethod()),
        );

        if (is_object($data)) {
            $groups[] = 'lemon_rest_' . $resource . '_view';
        } else {
            $groups[] = 'lemon_rest_' . $resource . '_list';
        }

        $context = SerializationContext::create()->enableMaxDepthChecks();
        $context->setGroups($groups);

        if ($accept->hasParameter('version')) {
            $context->setVersion($accept->getParameter('version'));
        }

        $output = $this->serializer->create('default')->serialize($data, $format, $context);

        $response->setContent($output);

        return $response;
    }

    public function options(Request $request, Response $response, $resource, $id = null)
    {
        $manager = $this->managerFactory->create($resource);

        $response->headers->set('Allowed', implode(", ", $manager->getOptions(!is_null($id))), true);
        $response->setContent(null);

        return $response;
    }
}
