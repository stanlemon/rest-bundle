<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineRegistryServicePass implements CompilerPassInterface
{
    const DOCTRINE_ORM = 'doctrine';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $doctrineServiceId = $container->getParameter('lemon_doctrine_registry_service_id');

        if ($doctrineServiceId != self::DOCTRINE_ORM) {
            $container->removeAlias('lemon_doctrine');

            if (!$container->hasDefinition($doctrineServiceId)) {
                throw new \RuntimeException(sprintf("Service %s is not configured", $doctrineServiceId));
            }

            $container->setAlias("lemon_doctrine", $doctrineServiceId);
        }
    }
}
