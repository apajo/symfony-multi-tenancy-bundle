<?php

namespace aPajo\MultiTenancyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('apajo_multi_tenancy');
    $rootNode = $treeBuilder->getRootNode();

    $rootNode
            ->children()
                ->arrayNode('adapters')
                    ->prototype('scalar')->end()
                    ->defaultValue([
                        'aPajo\\MultiTenancyBundle\\Adapter\\Database\\DatabaseAdapter',
                    ])
                ->end()
                ->arrayNode('tenant')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->isRequired()
                        ->end()
                        ->scalarNode('identifier')
                            ->defaultValue('key')
                        ->end()
                        ->scalarNode('entity_manager')
                            ->defaultValue('default')
                        ->end()
                        ->arrayNode('resolvers')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'aPajo\\MultiTenancyBundle\\Service\\Resolver\\HostBasedResolver',
                            ])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('migrations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default')
                            ->defaultValue('%kernel.project_dir%/config/migrations/default.yml')
                        ->end()
                        ->scalarNode('tenant')
                          ->defaultValue('%kernel.project_dir%/config/migrations/tenant.yml')
                        ->end()
                    ->end()
                ->end()
            ->end();

    return $treeBuilder;
  }
}
