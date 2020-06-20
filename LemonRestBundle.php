<?php

namespace Lemon\RestBundle;

use JMS\SerializerBundle\DependencyInjection\Compiler\ServiceMapPass;
use Lemon\RestBundle\DependencyInjection\Compiler\DoctrineRegistryServicePass;
use Lemon\RestBundle\DependencyInjection\Compiler\RegisterFormatPass;
use Lemon\RestBundle\DependencyInjection\Compiler\RegisterMappingsPass;
use Lemon\RestBundle\DependencyInjection\Compiler\RegisterResourcePass;
use Lemon\RestBundle\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class LemonRestBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        if (Kernel::MAJOR_VERSION == 2 && Kernel::MINOR_VERSION <=5) {
            $container->addCompilerPass(
                new \Symfony\Component\HttpKernel\DependencyInjection\RegisterListenersPass(
                    'lemon_rest.event_dispatcher',
                    'lemon_rest.event_listener',
                    'lemon_rest.event_subscriber'
                )
            );
        } else {
            $container->addCompilerPass(
                new \Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass(
                    'lemon_rest.event_dispatcher',
                    'lemon_rest.event_listener',
                    'lemon_rest.event_subscriber'
                )
            );
        }

        $container->addCompilerPass(new DoctrineRegistryServicePass());
        $container->addCompilerPass(new RegisterFormatPass());
        $container->addCompilerPass(new RegisterResourcePass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new RegisterMappingsPass());
        // This is basically copy-pasted from JMSSerializerBundle
        $container->addCompilerPass($this->getServiceMapPass(
            'jms_serializer.serialization_visitor',
            'format',
            function (ContainerBuilder $container, Definition $def) {
                $container->getDefinition('lemon_rest.serializer.constructor_factory')->replaceArgument(2, $def);
            }
        ));
        $container->addCompilerPass($this->getServiceMapPass(
            'jms_serializer.deserialization_visitor',
            'format',
            function (ContainerBuilder $container, Definition $def) {
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
