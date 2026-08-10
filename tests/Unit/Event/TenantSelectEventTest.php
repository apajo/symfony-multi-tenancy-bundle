<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Event;

use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantSelectEvent::class)]
class TenantSelectEventTest extends TestCase
{
  public function testCarriesTheSelectedTenant(): void
  {
    $tenant = (new Tenant())->setKey('acme');

    self::assertSame($tenant, (new TenantSelectEvent($tenant))->getTenant());
  }

  public function testNullTenantSignalsAReset(): void
  {
    // EnvironmentProvider::reset() dispatches this event with no tenant;
    // listeners treat null as "leave tenant context".
    self::assertNull((new TenantSelectEvent(null))->getTenant());
  }

  public function testTenantDefaultsToNull(): void
  {
    self::assertNull((new TenantSelectEvent())->getTenant());
  }
}
