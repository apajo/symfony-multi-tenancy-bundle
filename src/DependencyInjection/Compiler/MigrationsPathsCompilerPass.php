<?php

namespace aPajo\MultiTenancyBundle\DependencyInjection\Compiler;

use aPajo\MultiTenancyBundle\Migration\Command\DiffCommand;
use aPajo\MultiTenancyBundle\Migration\Command\MigrateCommand;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Yaml\Yaml;

class MigrationsPathsCompilerPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container): void
  {

    $configs = $container->getExtensionConfig('apajo_multi_tenancy');

    $config = array_merge($configs[0], [
      'migrations' => [
        'default' => $this->resolveConfigPath($container, $configs[0]['migrations']['default']),
        'tenant' => $this->resolveConfigPath($container, $configs[0]['migrations']['tenant']),
      ],
    ]);

    // Migration command configuration
    if ($container->hasDefinition(DiffCommand::class)) {
      $definition = $container->getDefinition(DiffCommand::class);
      $definition->addMethodCall(
        'setMigrationConfig',
        [$config['migrations']]
      );
    }

    if ($container->hasDefinition(MigrateCommand::class)) {
      $definition = $container->getDefinition(MigrateCommand::class);
      $definition->addMethodCall(
        'setMigrationConfig',
        [$config['migrations']]
      );
    }

    if ($container->hasDefinition(MigrationManager::class)) {
      $definition = $container->getDefinition(MigrationManager::class);
      $definition->addMethodCall(
        'setMigrationConfig',
        [$config['migrations']]
      );
    }

    if (!$container->hasDefinition('doctrine.migrations.configuration')) {
      return;
    }

    $definition = $container->getDefinition('doctrine.migrations.configuration');
    if (isset($config['migrations'])) {
      $this->load($container, $definition, $config['migrations']['default']);
      $this->load($container, $definition, $config['migrations']['tenant']);
    } else {
      $definition->addMethodCall('addMigrationsDirectory', ['App\Migrations\Default', '%kernel.project_dir%/migrations/default']);
      $definition->addMethodCall('addMigrationsDirectory', ['App\Migrations\Tenant', '%kernel.project_dir%/migrations/tenant']);
    }
  }

  protected function load(ContainerBuilder $container, Definition $definition, string $path): void
  {
    $config = $this->loadConfig($container, $path);

    if (!isset($config['migrations_paths'])) {
      throw new \Exception('No migrations paths found!');
    }

    foreach ($config['migrations_paths'] as $namespace => $path) {
      $definition->addMethodCall('addMigrationsDirectory', [$namespace, $path]);
    }
  }


  protected function loadConfig(ContainerBuilder $container, string $path): array
  {
    $config = Yaml::parseFile($path);

    return $config;
  }

  protected function resolveConfigPath(ContainerBuilder $container, string $path): ?string
  {
    // Look for configuration files in project root
    $projectDir = $container->getParameter('kernel.project_dir') . '/';
    $absolutePath = realpath($projectDir . $path);

    if (is_file($absolutePath)) {
      return $absolutePath;
    }

    // Otherwise look for configuration files in bundle root
    return realpath(__DIR__ . '/../../../' . $path);
  }
}
