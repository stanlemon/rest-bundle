<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterMappingsPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $registry = $container->getDefinition('lemon_rest.object_registry');

        $mappings = $container->getParameter('lemon_rest_mappings');

        foreach ($mappings as $mapping) {
            if (!class_exists($mapping['class'])) {
                throw new \RuntimeException(sprintf("Class \"%s\" does not exist", $mapping['class']));
            }

            $registry->addMethodCall('addClass', array($mapping['name'], $mapping['class']));
        }
    }
}
