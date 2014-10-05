<?php

namespace Lemon\RestBundle\Request;

use Lemon\RestBundle\Object\Exception\InvalidException;
use Lemon\RestBundle\Object\Exception\NotFoundException;
use Lemon\RestBundle\Object\ManagerFactory;
use Lemon\RestBundle\Object\Envelope\EnvelopeFactory;;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializerInterface;
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
     * @var \JMS\Serializer\SerializerInterface
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

    public function __construct(
        ManagerFactory $managerFactory,
        EnvelopeFactory $envelopeFactory,
        SerializerInterface $serializer,
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
        $manager = $this->managerFactory->create($resource);
        $class = $manager->getClass();

        $format = $this->negotiator->getBestFormat(
            $request->headers->get('Accept')
        );

        $response->headers->set('Content-Type', $request->headers->get('Accept'));

        try {
            $object = $this->serializer->deserialize(
                $request->getContent(),
                $class,
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

        $output = $this->serializer->serialize($data, $format);

        $response->setContent($output);

        return $response;
    }
}
