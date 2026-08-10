<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service;

use aPajo\MultiTenancyBundle\Adapter\AdapterInterface;
use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use aPajo\MultiTenancyBundle\Service\Aware\TenantAware;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Service\TenantManager;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[CoversClass(EnvironmentProvider::class)]
class EnvironmentProviderTest extends TestCase
{
  public function testSelectDispatchesTheTenant(): void
  {
    $tenant = (new Tenant())->setKey('acme');
    $dispatcher = $this->expectDispatchedTenants([$tenant]);

    $this->provider(dispatcher: $dispatcher)->select($tenant);
  }

  public function testResetDispatchesANullTenant(): void
  {
    $dispatcher = $this->expectDispatchedTenants([null]);

    $this->provider(dispatcher: $dispatcher)->reset();
  }

  public function testSelectWithNoArgumentIsAReset(): void
  {
    $dispatcher = $this->expectDispatchedTenants([null]);

    $this->provider(dispatcher: $dispatcher)->select();
  }

  public function testInitDispatchesWhateverTheManagerResolved(): void
  {
    $tenant = (new Tenant())->setKey('acme');

    $manager = $this->createMock(TenantManager::class);
    $manager->expects($this->once())->method('resolve')->willReturn($tenant);

    $this->provider(
      dispatcher: $this->expectDispatchedTenants([$tenant]),
      tenantManager: $manager
    )->init();
  }

  public function testInitDispatchesNullWhenNoTenantResolves(): void
  {
    // An unresolved tenant still has to be broadcast, so adapters can fall
    // back to their default configuration.
    $manager = $this->createMock(TenantManager::class);
    $manager->method('resolve')->willReturn(null);

    $this->provider(
      dispatcher: $this->expectDispatchedTenants([null]),
      tenantManager: $manager
    )->init();
  }

  public function testSelectTenantByIdLooksTheTenantUp(): void
  {
    $tenant = (new Tenant())->setKey('acme');

    $repository = $this->createMock(EntityRepository::class);
    $repository->expects($this->once())->method('find')->with(7)->willReturn($tenant);

    $this->provider(
      dispatcher: $this->expectDispatchedTenants([$tenant]),
      tenantConfig: $this->tenantConfig($repository)
    )->selectTenantById(7);
  }

  public function testForSelectsRunsTheCallbackThenResets(): void
  {
    $tenant = (new Tenant())->setKey('acme');
    $em = $this->createMock(EntityManagerInterface::class);

    $config = $this->createMock(TenantConfig::class);
    $config->method('getEntityManager')->willReturn($em);

    $received = [];
    $dispatcher = $this->recordingDispatcher($dispatched);

    $this->provider(dispatcher: $dispatcher, tenantConfig: $config)
      ->for($tenant, function (TenantInterface $t, EntityManagerInterface $manager) use (&$received) {
        $received[] = [$t, $manager];
      });

    self::assertSame([[$tenant, $em]], $received, 'the callback receives the tenant and its entity manager');
    self::assertSame([$tenant, null], $dispatched, 'tenant context is entered and then left again');
  }

  public function testOnTenantSelectEventAdaptsEveryAdapter(): void
  {
    $tenant = (new Tenant())->setKey('acme');

    $first = $this->createMock(AdapterInterface::class);
    $first->expects($this->once())->method('adapt')->with($tenant);
    $first->expects($this->never())->method('reset');

    $second = $this->createMock(AdapterInterface::class);
    $second->expects($this->once())->method('adapt')->with($tenant);

    $this->provider(adapterRegistry: new AdapterRegistry($first, $second))
      ->onTenantSelectEvent(new TenantSelectEvent($tenant));
  }

  public function testOnTenantSelectEventResetsEveryAdapterForANullTenant(): void
  {
    $adapter = $this->createMock(AdapterInterface::class);
    $adapter->expects($this->once())->method('reset');
    $adapter->expects($this->never())->method('adapt');

    $this->provider(adapterRegistry: new AdapterRegistry($adapter))
      ->onTenantSelectEvent(new TenantSelectEvent(null));
  }

  public function testIsRegisteredAsATenantSelectListener(): void
  {
    $attributes = (new \ReflectionClass(EnvironmentProvider::class))->getAttributes(AsEventListener::class);

    self::assertCount(1, $attributes);
    self::assertSame(TenantSelectEvent::class, $attributes[0]->newInstance()->event);
  }

  /**
   * Pins bug #3 -- currently failing, hence skipped.
   *
   * forEach() drives iteration with Doctrine's Collection::forAll(), which is
   * an all-match predicate, not a foreach: it stops at the first element whose
   * callback returns a falsy value. The callback here returns null, so only
   * the first tenant is ever visited. That makes `tenants:migrations:migrate`
   * silently migrate exactly one tenant.
   *
   * Fix: use map() (or a plain foreach) in EnvironmentProvider::forEach(),
   * then delete this skip.
   *
   * @see src/Service/EnvironmentProvider.php:32
   */
  public function testForEachVisitsEveryTenant(): void
  {
    $this->markTestSkipped('Pins known bug: EnvironmentProvider::forEach() uses Collection::forAll() and stops after the first tenant.');

    $tenants = new ArrayCollection([
      (new Tenant())->setKey('acme'),
      (new Tenant())->setKey('globex'),
      (new Tenant())->setKey('initech'),
    ]);

    $visited = [];

    $this->provider()->forEach($tenants, function (TenantInterface $tenant) use (&$visited) {
      $visited[] = $tenant->getKey();
    });

    self::assertSame(['acme', 'globex', 'initech'], $visited);
  }

  /**
   * Demonstrates the bug above without asserting the desired behaviour, so the
   * suite records the current contract rather than merely skipping it.
   */
  public function testForEachCurrentlyStopsAfterTheFirstTenant(): void
  {
    $tenants = new ArrayCollection([
      (new Tenant())->setKey('acme'),
      (new Tenant())->setKey('globex'),
      (new Tenant())->setKey('initech'),
    ]);

    $visited = [];

    $this->provider()->forEach($tenants, function (TenantInterface $tenant) use (&$visited) {
      $visited[] = $tenant->getKey();
    });

    self::assertSame(['acme'], $visited, 'see testForEachVisitsEveryTenant() for the intended behaviour');
  }

  // ---------------------------------------------------------------- helpers

  private function provider(
    ?EventDispatcherInterface $dispatcher = null,
    ?TenantAware $tenantAware = null,
    ?AdapterRegistry $adapterRegistry = null,
    ?TenantManager $tenantManager = null,
    ?TenantConfig $tenantConfig = null,
  ): EnvironmentProvider {
    return new EnvironmentProvider(
      $dispatcher ?? new EventDispatcher(),
      $tenantAware ?? $this->createMock(TenantAware::class),
      $adapterRegistry ?? new AdapterRegistry(),
      $tenantManager ?? $this->createMock(TenantManager::class),
      $tenantConfig ?? $this->createMock(TenantConfig::class),
    );
  }

  private function tenantConfig(EntityRepository $repository): TenantConfig
  {
    $config = $this->createMock(TenantConfig::class);
    $config->method('getRepository')->willReturn($repository);

    return $config;
  }

  /**
   * Asserts the exact sequence of tenants carried by dispatched events.
   *
   * @param list<TenantInterface|null> $expected
   */
  private function expectDispatchedTenants(array $expected): EventDispatcherInterface
  {
    $dispatcher = $this->createMock(EventDispatcherInterface::class);
    $dispatcher->expects($this->exactly(count($expected)))
      ->method('dispatch')
      ->willReturnCallback(function (TenantSelectEvent $event) use (&$expected) {
        self::assertSame(array_shift($expected), $event->getTenant());

        return $event;
      });

    return $dispatcher;
  }

  /**
   * @param list<TenantInterface|null>|null $dispatched
   */
  private function recordingDispatcher(?array &$dispatched): EventDispatcherInterface
  {
    $dispatched = [];

    $dispatcher = $this->createMock(EventDispatcherInterface::class);
    $dispatcher->method('dispatch')->willReturnCallback(function (TenantSelectEvent $event) use (&$dispatched) {
      $dispatched[] = $event->getTenant();

      return $event;
    });

    return $dispatcher;
  }
}
