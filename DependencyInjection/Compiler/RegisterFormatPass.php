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
        $formatNegotiator = $container->getDefinition('lemon_rest.format_negotiator');

        $formats = $container->getParameter('lemon_rest_formats');

        foreach ($formats as $format => $value) {
            $formatNegotiator->addMethodCall('registerFormat', array(
                $format,
                $value['mimeTypes'],
                true
            ));
        }
    }
}
