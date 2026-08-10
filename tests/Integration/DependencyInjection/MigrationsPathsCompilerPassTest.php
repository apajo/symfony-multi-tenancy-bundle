<?php

namespace aPajo\MultiTenancyBundle\Tests\Integration\DependencyInjection;

use aPajo\MultiTenancyBundle\DependencyInjection\Compiler\MigrationsPathsCompilerPass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(MigrationsPathsCompilerPass::class)]
class MigrationsPathsCompilerPassTest extends TestCase
{
  public function testRegistersBothMigrationNamespaces(): void
  {
    $container = new ContainerBuilder();
    $container->setDefinition('doctrine.migrations.configuration', new Definition(\stdClass::class));

    (new MigrationsPathsCompilerPass())->process($container);

    $calls = $container->getDefinition('doctrine.migrations.configuration')->getMethodCalls();

    self::assertSame([
      ['addMigrationsDirectory', ['App\Migrations\Default', '%kernel.project_dir%/migrations/default']],
      ['addMigrationsDirectory', ['App\Migrations\Tenant', '%kernel.project_dir%/migrations/tenant']],
    ], $calls);
  }

  public function testIsANoOpWithoutDoctrineMigrations(): void
  {
    // The bundle must stay loadable in an application that has DoctrineBundle
    // but not DoctrineMigrationsBundle.
    $container = new ContainerBuilder();

    (new MigrationsPathsCompilerPass())->process($container);

    self::assertFalse($container->hasDefinition('doctrine.migrations.configuration'));
  }

  public function testExistingMethodCallsArePreserved(): void
  {
    $definition = new Definition(\stdClass::class);
    $definition->addMethodCall('addMigrationsDirectory', ['App\Migrations\Other', '/elsewhere']);

    $container = new ContainerBuilder();
    $container->setDefinition('doctrine.migrations.configuration', $definition);

    (new MigrationsPathsCompilerPass())->process($container);

    self::assertCount(3, $container->getDefinition('doctrine.migrations.configuration')->getMethodCalls());
  }

  /**
   * The namespaces and paths above are hardcoded: the pass ignores the
   * `apajo_multi_tenancy.migrations` config it sits next to (the
   * getExtensionConfig() call is commented out in the source). Recorded here so
   * the limitation is visible rather than surprising.
   *
   * @see src/DependencyInjection/Compiler/MigrationsPathsCompilerPass.php:16
   */
  public function testPathsAreNotDerivedFromBundleConfiguration(): void
  {
    $container = new ContainerBuilder();
    $container->setDefinition('doctrine.migrations.configuration', new Definition(\stdClass::class));
    $container->prependExtensionConfig('apajo_multi_tenancy', [
      'migrations' => ['tenant' => 'config/custom/tenant.yml'],
    ]);

    (new MigrationsPathsCompilerPass())->process($container);

    $calls = $container->getDefinition('doctrine.migrations.configuration')->getMethodCalls();

    self::assertSame('%kernel.project_dir%/migrations/tenant', $calls[1][1][1]);
  }
}
