<?php
namespace Lemon\RestBundle\Event;

use Lemon\RestBundle\Object\Processor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProcessorSubscriber implements EventSubscriberInterface
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @param Processor $processor
     */
    public function __construct(Processor $processor)
    {
        $this->processor = $processor;
    }

    public static function getSubscribedEvents()
    {
        return array(
            RestEvents::PRE_CREATE => array('preCreate', 1000),
            RestEvents::PRE_UPDATE => array('preUpdate', 1000),
        );
    }

    /**
     * @param ObjectEvent $event
     */
    public function preCreate(ObjectEvent $event)
    {
        $this->processor->process($event->getObject());
    }

    /**
     * @param ObjectEvent $event
     */
    public function preUpdate(ObjectEvent $event)
    {
        $this->processor->process($event->getObject(), $event->getOriginal());
    }
}
