<?php

namespace aPajo\MultiTenancyBundle\Tests\Database;

use aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter;
use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use aPajo\MultiTenancyBundle\Tests\Entity\Tenant\Item;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The bundle's core promise: selecting a tenant redirects the `tenant`
 * connection at that tenant's database, and deselecting puts it back.
 */
#[CoversClass(DatabaseAdapter::class)]
class DatabaseAdapterTest extends AbstractDatabaseTestCase
{
  private ContainerInterface $container;

  protected function setUp(): void
  {
    parent::setUp();

    $this->container = $this->boot();
  }

  private function connection(): Connection
  {
    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');

    return $doctrine->getConnection('tenant');
  }

  private function adapter(): DatabaseAdapter
  {
    return $this->container->get(DatabaseAdapter::class);
  }

  private function tenant(string $schema): Tenant
  {
    return (new Tenant())->setKey('acme')->setDatabase(self::tenantDsn($schema));
  }

  public function testTenantConnectionStartsOnTheConfiguredSchema(): void
  {
    self::assertSame(self::TENANT_SCHEMA, $this->connection()->getDatabase());
  }

  public function testAdaptingRedirectsTheTenantConnection(): void
  {
    $this->adapter()->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    self::assertSame(self::ALTERNATE_TENANT_SCHEMA, $this->connection()->getDatabase());
  }

  public function testAdaptedConnectionIsUsable(): void
  {
    $this->adapter()->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    self::assertSame(
      self::ALTERNATE_TENANT_SCHEMA,
      $this->connection()->fetchOne('SELECT DATABASE()'),
      'the live session, not just the parameters, must be on the new schema'
    );
  }

  public function testResetReturnsToTheOriginalSchema(): void
  {
    $adapter = $this->adapter();
    $adapter->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    $adapter->reset();

    self::assertSame(self::TENANT_SCHEMA, $this->connection()->getDatabase());
    self::assertSame(self::TENANT_SCHEMA, $this->connection()->fetchOne('SELECT DATABASE()'));
  }

  public function testSwitchingBetweenTenantsMovesTheConnectionEachTime(): void
  {
    $adapter = $this->adapter();

    $adapter->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));
    self::assertSame(self::ALTERNATE_TENANT_SCHEMA, $this->connection()->getDatabase());

    $adapter->adapt($this->tenant(self::TENANT_SCHEMA));
    self::assertSame(self::TENANT_SCHEMA, $this->connection()->getDatabase());
  }

  public function testNonUrlConnectionSettingsSurviveTheSwitch(): void
  {
    // getConnectionParams() merges the tenant DSN over the captured defaults,
    // so charset and friends must not be lost.
    $before = $this->connection()->getParams();

    $this->adapter()->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    $after = $this->connection()->getParams();

    self::assertSame($before['charset'] ?? null, $after['charset'] ?? null);
    self::assertArrayNotHasKey('url', $after, 'a leftover url would override the tenant parameters');
  }

  public function testSelectingATenantThroughTheEventPipelineSwitchesTheConnection(): void
  {
    // Same outcome as calling the adapter directly, but driven the way the
    // bundle actually does it at runtime.
    $this->container->get(EnvironmentProvider::class)
      ->select($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    self::assertSame(self::ALTERNATE_TENANT_SCHEMA, $this->connection()->getDatabase());
  }

  public function testResettingThroughTheEventPipelineRestoresTheConnection(): void
  {
    $provider = $this->container->get(EnvironmentProvider::class);
    $provider->select($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    $provider->reset();

    self::assertSame(self::TENANT_SCHEMA, $this->connection()->getDatabase());
  }

  public function testTenantSelectEventWithNoTenantResetsAdapters(): void
  {
    $adapter = $this->adapter();
    $adapter->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));

    $this->container->get(EnvironmentProvider::class)
      ->onTenantSelectEvent(new TenantSelectEvent(null));

    self::assertSame(self::TENANT_SCHEMA, $this->connection()->getDatabase());
  }

  public function testWritesLandInTheSelectedTenantSchema(): void
  {
    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');
    $em = $doctrine->getManager('tenant');

    $this->adapter()->adapt($this->tenant(self::ALTERNATE_TENANT_SCHEMA));
    $this->resetSchema($em);

    $em->persist((new Item())->setName('only-in-alpha'));
    $em->flush();
    $em->clear();

    self::assertSame(
      self::ALTERNATE_TENANT_SCHEMA,
      $this->connection()->fetchOne('SELECT DATABASE()')
    );
    self::assertCount(1, $em->getRepository(Item::class)->findAll());

    // The row must not be visible from the other tenant schema.
    $this->adapter()->adapt($this->tenant(self::TENANT_SCHEMA));
    $this->resetSchema($em);
    $em->clear();

    self::assertCount(0, $em->getRepository(Item::class)->findAll());
  }
}
