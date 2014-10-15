<?php

namespace Lemon\RestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;
use Symfony\Component\DependencyInjection\Loader;

class Extension extends BaseExtension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('lemon_rest_object_envelope_class', $config['envelope']);
        $container->setParameter('lemon_rest_mappings', $config['mappings']);
        $container->setParameter('lemon_rest_order_by_keyword', $config['order_by_keyword']);
        $container->setParameter('lemon_rest_order_dir_keyword', $config['order_dir_keyword']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'lemon_rest';
    }
}
