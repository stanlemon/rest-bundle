<?php

namespace Lemon\RestBundle;

use Lemon\RestBundle\DependencyInjection\Compiler\RegisterResourcePass;
use Lemon\RestBundle\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LemonRestBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterResourcePass());
        $container->addCompilerPass(new RegisterListenersPass(
            'lemon_rest.event_dispatcher',
            'lemon_rest.event_listener',
            'lemon_rest.event_subscriber'
        ));
    }

    public function getContainerExtension()
    {
        return new Extension();
    }
}
