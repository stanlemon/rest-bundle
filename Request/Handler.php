<?php

namespace Lemon\RestBundle\Request;

use Lemon\RestBundle\Object\Exception\InvalidException;
use Lemon\RestBundle\Object\Exception\NotFoundException;
use Lemon\RestBundle\Object\Exception\UnsupportedMethodException;
use Lemon\RestBundle\Object\ManagerFactory;
use Lemon\RestBundle\Object\Envelope\EnvelopeFactory;
use Lemon\RestBundle\Serializer\ConstructorFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Negotiation\FormatNegotiatorInterface;

class Handler
{
    /**
     * @var \Lemon\RestBundle\Object\ManagerFactory
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
     * @var \Negotiation\FormatNegotiatorInterface
     */
    protected $negotiator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerFactory $managerFactory
     * @param EnvelopeFactory $envelopeFactory
     * @param SerializerInterface $serializer
     * @param FormatNegotiatorInterface $negotiator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerFactory $managerFactory,
        EnvelopeFactory $envelopeFactory,
        ConstructorFactory $serializer,
        FormatNegotiatorInterface $negotiator,
        LoggerInterface $logger
    ) {
        $this->managerFactory = $managerFactory;
        $this->envelopeFactory = $envelopeFactory;
        $this->serializer = $serializer;
        $this->negotiator = $negotiator;
        $this->logger = $logger;
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

            $object = $this->serializer->create(
                $request->isMethod('patch') ? 'doctrine' : 'default'
            )->deserialize(
                $request->getContent(),
                $manager->getClass(),
                $format
            );

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
                "message" => $e->getMessage(),
            );
        }

        $context = SerializationContext::create();

        if ($accept->hasParameter('version')) {
            $context->setVersion($accept->getParameter('version'));
        }

        $output = $this->serializer->create('default')->serialize($data, $format, $context);

        $response->setContent($output);

        return $response;
    }
}
