<?php

namespace Lemon\RestBundle;

use Lemon\RestBundle\DependencyInjection\Compiler\RegisterResourcePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LemonRestBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterResourcePass());
    }
}
