<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AuthorizationCheckerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $authorizationCheckerServiceId = $container->getParameter('lemon_rest_authorization_checker_service_id');

        if (!$container->hasDefinition($authorizationCheckerServiceId)) {
            throw new \RuntimeException(sprintf("Service %s is not configured", $authorizationCheckerServiceId));
        }

        $container->setAlias("lemon_rest.authorization_checker", $authorizationCheckerServiceId);
    }
}
