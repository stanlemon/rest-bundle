<?php
namespace Lemon\RestBundle\Event;

use Lemon\RestBundle\Object\Validator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ValidatorSubscriber implements EventSubscriberInterface
{
    protected $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public static function getSubscribedEvents()
    {
        return array(
            RestEvents::PRE_CREATE => array('preCreate', -1000),
            RestEvents::PRE_UPDATE => array('preUpdate', -1000),
        );
    }

    public function preCreate(ObjectEvent $event)
    {
        $this->validator->validate($event->getObject());
    }

    public function preUpdate(ObjectEvent $event)
    {
        $this->validator->validate($event->getObject());
    }
}
