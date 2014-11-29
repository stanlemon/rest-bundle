<?php

namespace Lemon\RestBundle;

use JMS\SerializerBundle\DependencyInjection\Compiler\ServiceMapPass;
use Lemon\RestBundle\DependencyInjection\Compiler\RegisterFormatPass;
use Lemon\RestBundle\DependencyInjection\Compiler\RegisterMappingsPass;
use Lemon\RestBundle\DependencyInjection\Compiler\RegisterResourcePass;
use Lemon\RestBundle\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LemonRestBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterFormatPass());
        $container->addCompilerPass(new RegisterResourcePass());
        $container->addCompilerPass(new RegisterMappingsPass());
        $container->addCompilerPass(new RegisterListenersPass(
            'lemon_rest.event_dispatcher',
            'lemon_rest.event_listener',
            'lemon_rest.event_subscriber'
        ));
        // This is basically copy-pasted from JMSSerializerBundle
        $container->addCompilerPass($this->getServiceMapPass('jms_serializer.serialization_visitor', 'format',
            function(ContainerBuilder $container, Definition $def) {
                $container->getDefinition('lemon_rest.serializer.constructor_factory')->replaceArgument(2, $def);
            }
        ));
        $container->addCompilerPass($this->getServiceMapPass('jms_serializer.deserialization_visitor', 'format',
            function(ContainerBuilder $container, Definition $def) {
                $container->getDefinition('lemon_rest.serializer.constructor_factory')->replaceArgument(3, $def);
            }
        ));
    }

    public function getContainerExtension()
    {
        return new Extension();
    }

    protected function getServiceMapPass($tagName, $keyAttributeName, $callable)
    {
        return new ServiceMapPass($tagName, $keyAttributeName, $callable);
    }
}
