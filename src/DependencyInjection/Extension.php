<?php

namespace aPajo\MultiTenancyBundle\DependencyInjection;

use aPajo\MultiTenancyBundle\APajoMultiTenancyBundle;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension as Base;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class Extension extends Base
{

  /**
   * {@inheritdoc}
   */
  public function load(array $configs, ContainerBuilder $container): void
  {
    $this->loadConfig($configs, $container, 'services.yml');

    $configuration = new Configuration();
    $cfg = $this->processConfiguration($configuration, $configs);

    $bundles = $container->getParameter('kernel.bundles');

    if (!isset($bundles['DoctrineBundle'])) {
      throw new InvalidConfigurationException('DoctrineBundle is required by ' . APajoMultiTenancyBundle::class);
    }

    // Adapter registry
    if ($container->hasDefinition(AdapterRegistry::class)) {
      $definition = $container->getDefinition(AdapterRegistry::class);

      foreach ($cfg['adapters'] as $adapter) {
        $adapterReference = new Reference($adapter);
        $definition->addArgument($adapterReference);
      }
    }

    // Resolver registry
    if ($container->hasDefinition(ResolverRegistry::class)) {
      $definition = $container->getDefinition(ResolverRegistry::class);

      foreach ($cfg['tenant']['resolvers'] as $resolver) {
        $resolverReference = new Reference($resolver);
        $definition->addArgument($resolverReference);
      }
    }

    // Config service
    if ($container->hasDefinition(TenantConfig::class)) {
      $definition = $container->getDefinition(TenantConfig::class);
      $entityManagerServiceId = str_contains((string) $cfg['tenant']['entity_manager'], '.') ?
        $cfg['tenant']['entity_manager'] :
        sprintf('doctrine.orm.%s_entity_manager', $cfg['tenant']['entity_manager']);

      $emReference = new Reference($entityManagerServiceId);

      $definition->setArgument(0, $cfg);
      $definition->setArgument(1, $emReference);
    }

    // Set monolog er logger if exists
    if (isset($bundles['MonologBundle'])) {
      $definition = $container->getDefinition(EnvironmentProvider::class);
      $definition->addMethodCall(
        'setLogger',
        [new Reference('monolog.logger')]
      );
    }
  }

  protected function loadConfig(array $configs, ContainerBuilder $container, $name)
  {
    $location = realpath(__DIR__ . '/../Resources/config');
    $loader = new Loader\YamlFileLoader($container, new FileLocator($location));
    $loader->load($name);
  }

  public function getContainerExtension(): string
  {
    return 'apajo_multi_tenancy';
  }

  public function getAlias(): string
  {
    return 'apajo_multi_tenancy';
  }
}
