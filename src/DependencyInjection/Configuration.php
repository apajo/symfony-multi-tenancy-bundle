<?php

namespace aPajo\MultiTenancyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder('apajo_multi_tenancy');
    $rootNode = $treeBuilder->getRootNode();

    $rootNode
      ->children()
          ->arrayNode('adapters')
              ->prototype('scalar')->end()
          ->end()
          ->arrayNode('tenant')
              ->children()
                  ->scalarNode('class')->end()
                  ->scalarNode('identifier')->end()
                  ->scalarNode('entity_manager')->end()
                  ->arrayNode('resolvers')
                      ->scalarPrototype()->end()
                  ->end()
              ->end()
          ->end()
          ->arrayNode('migrations')
              ->children()
                  ->scalarNode('namespace')->end()
                  ->scalarNode('path')->end()
              ->end()
          ->end()
      ->end()
    ;

    return $treeBuilder;
  }
}
