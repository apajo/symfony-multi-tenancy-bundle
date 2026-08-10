<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Adapter;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterInterface;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterTrait;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(PropertyAdapterTrait::class)]
class PropertyAdapterTraitTest extends TestCase
{
  public function testAdaptReadsTheConfiguredPropertyAndPassesAParsedDsn(): void
  {
    $adapter = $this->adapter();
    $tenant = (new Tenant())->setDatabase('mysql://tenant:secret@127.0.0.1:3307/tenant_one');

    $adapter->adapt($tenant);

    self::assertInstanceOf(Dsn::class, $adapter->adapted);
    self::assertSame('mysql', $adapter->adapted->getScheme());
    self::assertSame('127.0.0.1', $adapter->adapted->getHost());
    self::assertSame('tenant', $adapter->adapted->getUser());
    self::assertSame(3307, $adapter->adapted->getPort());
    self::assertSame('/tenant_one', $adapter->adapted->getPath());
  }

  public function testFirstAdaptSnapshotsTheDefaultConfiguration(): void
  {
    $adapter = $this->adapter();

    $adapter->adapt($this->tenantWithDsn());

    self::assertSame(1, $adapter->captureCalls);
    self::assertSame('the-default', $adapter->exposedDefault());
  }

  public function testDefaultIsCapturedOnlyOnce(): void
  {
    // The snapshot must stay the original configuration; capturing again after
    // a tenant is active would record that tenant's settings as the default.
    $adapter = $this->adapter();

    $adapter->adapt($this->tenantWithDsn());
    $adapter->adapt($this->tenantWithDsn('mysql://other@127.0.0.1/tenant_two'));

    self::assertSame(1, $adapter->captureCalls);
  }

  public function testTenantWithoutAValueFallsBackToTheDefault(): void
  {
    // Previously this returned null and silently left the previous tenant's
    // configuration active.
    $adapter = $this->adapter();
    $adapter->adapt($this->tenantWithDsn());
    $adapter->adapted = null;

    $adapter->adapt(new Tenant());

    self::assertSame(['the-default'], $adapter->restored);
    self::assertNull($adapter->adapted, 'doAdapt() must not run for a tenant with no DSN');
  }

  public function testEmptyStringIsTreatedAsNoValue(): void
  {
    $adapter = $this->adapter();
    $adapter->adapt($this->tenantWithDsn());
    $adapter->adapted = null;

    $adapter->adapt((new Tenant())->setDatabase(''));

    self::assertSame(['the-default'], $adapter->restored);
    self::assertNull($adapter->adapted);
  }

  public function testResetRestoresTheCapturedDefault(): void
  {
    $adapter = $this->adapter();
    $adapter->adapt($this->tenantWithDsn());

    $adapter->reset();

    self::assertSame(['the-default'], $adapter->restored);
  }

  public function testResetBeforeAnyAdaptIsANoOp(): void
  {
    // Nothing has been replaced yet, so there is nothing to put back -- and no
    // snapshot exists to restore from.
    $adapter = $this->adapter();

    self::assertNull($adapter->reset());
    self::assertSame([], $adapter->restored);
    self::assertSame(0, $adapter->captureCalls);
  }

  public function testResetIsIdempotent(): void
  {
    $adapter = $this->adapter();
    $adapter->adapt($this->tenantWithDsn());

    $adapter->reset();
    $adapter->reset();

    self::assertSame(['the-default', 'the-default'], $adapter->restored);
    self::assertSame(1, $adapter->captureCalls);
  }

  public function testSetPropertyRedirectsWhichTenantFieldIsRead(): void
  {
    $adapter = $this->adapter();
    $adapter->setProperty('host');

    $adapter->adapt((new Tenant())->setHost('smtp://mail.example.com'));

    self::assertNotNull($adapter->adapted);
    self::assertSame('mail.example.com', $adapter->adapted->getHost());
  }

  private function tenantWithDsn(string $dsn = 'mysql://tenant:secret@127.0.0.1:3307/tenant_one'): Tenant
  {
    return (new Tenant())->setDatabase($dsn);
  }

  /**
   * Minimal consumer of the trait: records what it was asked to do.
   */
  private function adapter(): PropertyAdapterInterface
  {
    return new class implements PropertyAdapterInterface {
      use PropertyAdapterTrait;

      protected string $property = 'database';

      public ?Dsn $adapted = null;
      public int $captureCalls = 0;
      /** @var list<mixed> */
      public array $restored = [];

      public function doAdapt(Dsn $dsn)
      {
        $this->adapted = $dsn;

        return $dsn;
      }

      protected function captureDefault(): mixed
      {
        ++$this->captureCalls;

        return 'the-default';
      }

      protected function restoreDefault(mixed $default): mixed
      {
        $this->restored[] = $default;

        return null;
      }

      public function exposedDefault(): mixed
      {
        return $this->getDefault();
      }
    };
  }
}
