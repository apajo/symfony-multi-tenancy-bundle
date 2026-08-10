<?php

namespace aPajo\MultiTenancyBundle\Tests\Integration;

use aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter;
use aPajo\MultiTenancyBundle\Adapter\Mailer\MailerAdapter;
use aPajo\MultiTenancyBundle\Event\Listener\RequestListener;
use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use aPajo\MultiTenancyBundle\Service\Aware\TenantAware;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use PHPUnit\Framework\Attributes\DataProvider;
use aPajo\MultiTenancyBundle\Tests\AbstractKernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Boots the real test kernel.
 *
 * This is the test that would have caught the harness being broken: it fails
 * the moment a config file stops being loaded, a service stops resolving, or a
 * required bundle goes missing. It needs no database -- Doctrine connections
 * are lazy, so nothing here opens one.
 */
class ServiceWiringTest extends AbstractKernelTestCase
{
  public function testKernelBoots(): void
  {
    $this->boot();

    self::assertSame('test', self::$kernel->getEnvironment());
  }

  public function testRequiredBundlesAreRegistered(): void
  {
    $this->boot();

    $bundles = array_keys(self::$kernel->getBundles());

    self::assertContains('FrameworkBundle', $bundles);
    self::assertContains('DoctrineBundle', $bundles);
    self::assertContains('DoctrineMigrationsBundle', $bundles);
    self::assertContains('APajoMultiTenancyBundle', $bundles);
  }

  #[DataProvider('serviceProvider')]
  public function testBundleServicesResolve(string $id): void
  {
    self::assertInstanceOf($id, $this->boot()->get($id));
  }

  public static function serviceProvider(): iterable
  {
    yield 'environment provider' => [EnvironmentProvider::class];
    yield 'tenant manager' => [\aPajo\MultiTenancyBundle\Service\TenantManager::class];
    yield 'tenant config' => [TenantConfig::class];
    yield 'tenant aware' => [TenantAware::class];
    yield 'adapter registry' => [AdapterRegistry::class];
    yield 'resolver registry' => [ResolverRegistry::class];
    yield 'host based resolver' => [HostBasedResolver::class];
    yield 'database adapter' => [DatabaseAdapter::class];
    yield 'mailer adapter' => [MailerAdapter::class];
    yield 'migration manager' => [MigrationManager::class];
    yield 'request listener' => [RequestListener::class];
  }

  public function testTenantConfigReflectsTheBundleConfiguration(): void
  {
    $config = $this->boot()->get(TenantConfig::class);

    self::assertSame('key', $config->getTenantIdentifierColumn());
    self::assertSame(
      Tenant::class,
      $config->getRepository()->getClassName(),
      'the repository should be built from apajo_multi_tenancy.tenant.class'
    );
  }

  public function testConfiguredAdaptersAreInTheRegistry(): void
  {
    $adapters = $this->boot()->get(AdapterRegistry::class)->getAdapters();

    $classes = array_map('get_class', $adapters->toArray());

    self::assertContains(DatabaseAdapter::class, $classes);
    self::assertContains(MailerAdapter::class, $classes);
  }

  public function testConfiguredResolversAreInTheRegistry(): void
  {
    $resolvers = $this->boot()->get(ResolverRegistry::class)->getResolvers();

    self::assertSame([HostBasedResolver::class], array_map('get_class', $resolvers->toArray()));
  }

  public function testRequestListenerIsSubscribedToKernelRequest(): void
  {
    /** @var EventDispatcherInterface $dispatcher */
    $dispatcher = $this->boot()->get('event_dispatcher');

    $listeners = array_map(
      static fn (array $listener): string => get_class($listener[0]),
      $dispatcher->getListeners('kernel.request')
    );

    self::assertContains(RequestListener::class, $listeners);
  }

  public function testTenantSelectListenersAreSubscribed(): void
  {
    /** @var EventDispatcherInterface $dispatcher */
    $dispatcher = $this->boot()->get('event_dispatcher');

    $listeners = array_map(
      static fn (array $listener): string => get_class($listener[0]),
      $dispatcher->getListeners(TenantSelectEvent::class)
    );

    self::assertContains(TenantAware::class, $listeners);
    self::assertContains(EnvironmentProvider::class, $listeners);
  }

  public function testBothEntityManagersAreConfigured(): void
  {
    $doctrine = $this->boot()->get('doctrine');

    self::assertArrayHasKey('default', $doctrine->getManagerNames());
    self::assertArrayHasKey('tenant', $doctrine->getManagerNames());
  }

  public function testFixtureEntitiesAreMappedToTheExpectedManagers(): void
  {
    $doctrine = $this->boot()->get('doctrine');

    self::assertSame(
      'default',
      $doctrine->getManagerForClass(Tenant::class) === $doctrine->getManager('default') ? 'default' : 'other',
      'the tenant registry must live on the connection that is never swapped'
    );

    self::assertNotNull(
      $doctrine->getManager('tenant')->getClassMetadata(\aPajo\MultiTenancyBundle\Tests\Entity\Tenant\Item::class)
    );
  }

  public function testMigrationCommandsAreAvailable(): void
  {
    $container = $this->boot();

    self::assertTrue($container->has(\aPajo\MultiTenancyBundle\Migration\Command\DiffCommand::class));
    self::assertTrue($container->has(\aPajo\MultiTenancyBundle\Migration\Command\MigrateCommand::class));
  }
}
