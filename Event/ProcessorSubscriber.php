<?php
namespace Lemon\RestBundle\Event;

use Lemon\RestBundle\Object\Processor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProcessorSubscriber implements EventSubscriberInterface
{
    protected $processor;

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

    public function preCreate(ObjectEvent $event)
    {
        $this->processor->process($event->getObject());
    }

    public function preUpdate(ObjectEvent $event)
    {
        $this->processor->process($event->getObject(), $event->getOriginal());
    }
}
