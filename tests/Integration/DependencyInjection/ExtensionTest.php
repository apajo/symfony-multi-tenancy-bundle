<?php

namespace aPajo\MultiTenancyBundle\Tests\Integration\DependencyInjection;

use aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter;
use aPajo\MultiTenancyBundle\Adapter\Mailer\MailerAdapter;
use aPajo\MultiTenancyBundle\DependencyInjection\Extension;
use aPajo\MultiTenancyBundle\Migration\Command\DiffCommand;
use aPajo\MultiTenancyBundle\Migration\Command\MigrateCommand;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Exercises the extension against a bare ContainerBuilder -- no kernel, so
 * these run fast and pinpoint wiring mistakes without a full boot.
 */
#[CoversClass(Extension::class)]
class ExtensionTest extends TestCase
{
  private function load(array $config = [], array $bundles = ['DoctrineBundle' => 'x']): ContainerBuilder
  {
    $container = new ContainerBuilder();
    $container->setParameter('kernel.bundles', $bundles);
    $container->setParameter('kernel.project_dir', '/app');

    (new Extension())->load([$this->config($config)], $container);

    return $container;
  }

  private function config(array $overrides = []): array
  {
    return array_merge_recursive(['tenant' => ['class' => Tenant::class]], $overrides);
  }

  public function testAliasIsStable(): void
  {
    // Changing this silently breaks every consumer's config file.
    self::assertSame('apajo_multi_tenancy', (new Extension())->getAlias());
  }

  public function testDoctrineBundleIsMandatory(): void
  {
    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessage('DoctrineBundle is required');

    $this->load(bundles: ['FrameworkBundle' => 'x']);
  }

  public function testCoreServicesAreRegistered(): void
  {
    $container = $this->load();

    foreach ([TenantConfig::class, AdapterRegistry::class, ResolverRegistry::class, HostBasedResolver::class] as $id) {
      self::assertTrue($container->hasDefinition($id), sprintf('%s should be defined', $id));
    }
  }

  public function testMigrationConfigIsInjectedIntoEveryMigrationService(): void
  {
    $container = $this->load(['migrations' => ['tenant' => 'config/custom/tenant.yml']]);

    foreach ([DiffCommand::class, MigrateCommand::class, MigrationManager::class] as $id) {
      $calls = $container->getDefinition($id)->getMethodCalls();
      $setters = array_values(array_filter($calls, static fn (array $c): bool => $c[0] === 'setMigrationConfig'));

      self::assertCount(1, $setters, sprintf('%s should receive setMigrationConfig() once', $id));
      self::assertSame('config/custom/tenant.yml', $setters[0][1][0]['tenant']);
    }
  }

  public function testConfiguredAdaptersBecomeRegistryArguments(): void
  {
    $container = $this->load(['adapters' => [DatabaseAdapter::class, MailerAdapter::class]]);

    $arguments = $container->getDefinition(AdapterRegistry::class)->getArguments();

    self::assertCount(2, $arguments);
    self::assertContainsOnlyInstancesOf(Reference::class, $arguments);
    self::assertSame(
      [DatabaseAdapter::class, MailerAdapter::class],
      array_map('strval', $arguments)
    );
  }

  public function testDefaultAdapterIsUsedWhenNoneAreConfigured(): void
  {
    $arguments = $this->load()->getDefinition(AdapterRegistry::class)->getArguments();

    self::assertSame([DatabaseAdapter::class], array_map('strval', $arguments));
  }

  public function testConfiguredResolversBecomeRegistryArguments(): void
  {
    $container = $this->load(['tenant' => ['resolvers' => [HostBasedResolver::class]]]);

    $arguments = $container->getDefinition(ResolverRegistry::class)->getArguments();

    self::assertSame([HostBasedResolver::class], array_map('strval', $arguments));
  }

  public function testTenantConfigReceivesTheProcessedConfiguration(): void
  {
    $config = $this->load()->getDefinition(TenantConfig::class)->getArgument(0);

    self::assertIsArray($config);
    self::assertSame(Tenant::class, $config['tenant']['class']);
    self::assertSame('key', $config['tenant']['identifier']);
  }

  public function testShorthandEntityManagerNameIsExpandedToADoctrineServiceId(): void
  {
    $container = $this->load(['tenant' => ['entity_manager' => 'default']]);

    self::assertSame(
      'doctrine.orm.default_entity_manager',
      (string) $container->getDefinition(TenantConfig::class)->getArgument(1)
    );
  }

  public function testNonDefaultEntityManagerNameIsExpandedToo(): void
  {
    $container = $this->load(['tenant' => ['entity_manager' => 'tenant']]);

    self::assertSame(
      'doctrine.orm.tenant_entity_manager',
      (string) $container->getDefinition(TenantConfig::class)->getArgument(1)
    );
  }

  public function testAnExplicitServiceIdIsPassedThroughUntouched(): void
  {
    // A value containing a dot is treated as a service id, not a shorthand.
    $container = $this->load(['tenant' => ['entity_manager' => 'my.custom.entity_manager']]);

    self::assertSame(
      'my.custom.entity_manager',
      (string) $container->getDefinition(TenantConfig::class)->getArgument(1)
    );
  }

  public function testMailerAdapterIsTaggedAsATransportFactory(): void
  {
    $tags = $this->load()->getDefinition(MailerAdapter::class)->getTag('mailer.transport_factory');

    self::assertCount(1, $tags);
  }

  public function testDatabaseAdapterReceivesTheDoctrineRegistry(): void
  {
    $arguments = $this->load()->getDefinition(DatabaseAdapter::class)->getArguments();

    self::assertSame('doctrine', (string) $arguments['$doctrine']);
  }
}
