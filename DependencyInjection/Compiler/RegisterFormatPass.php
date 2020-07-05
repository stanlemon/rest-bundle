<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterFormatPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $negotiator = $container->getDefinition('lemon_rest.negotiator');

        $formats = $container->getParameter('lemon_rest_formats');

        foreach ($formats as $format => $value) {
            $negotiator->addMethodCall('registerFormat', array(
                $format,
                $value['mimeTypes'],
                true
            ));
        }
    }
}
