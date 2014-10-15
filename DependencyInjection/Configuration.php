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
                ->scalarNode('criteria')
                    ->defaultValue('Lemon\RestBundle\Object\Criteria\DefaultCriteria')
                    ->end()
                ->arrayNode('mappings')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('class')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
