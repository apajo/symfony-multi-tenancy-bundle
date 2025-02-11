<?php

namespace aPajo\MultiTenancyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MigrationsPathsCompilerPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container): void
  {
    if (!$container->hasDefinition('doctrine.migrations.configuration')) {
      return;
    }

    $definition = $container->getDefinition('doctrine.migrations.configuration');
    // $config = $container->getExtensionConfig('apajo_multi_tenancy');

    $definition->addMethodCall('addMigrationsDirectory', ['App\Migrations\Default', '%kernel.project_dir%/migrations/default']);
    $definition->addMethodCall('addMigrationsDirectory', ['App\Migrations\Tenant', '%kernel.project_dir%/migrations/tenant']);
  }
}
