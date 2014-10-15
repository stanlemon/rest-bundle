<?php
namespace Lemon\RestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('lemon_rest');
        $rootNode
            ->children()
                ->scalarNode('envelope')
                    ->defaultValue('Lemon\RestBundle\Object\Envelope\DefaultEnvelope')
                    ->end()
                ->arrayNode('mappings')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('class')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('order_by_keyword')
                  ->defaultValue('_orderBy')
                  ->end()
                ->scalarNode('order_dir_keyword')
                  ->defaultValue('_orderDir')
                  ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
