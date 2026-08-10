<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service\Aware;

use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use aPajo\MultiTenancyBundle\Service\Aware\TenantAware;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[CoversClass(TenantAware::class)]
class TenantAwareTest extends TestCase
{
  public function testSelectingATenantMakesItCurrent(): void
  {
    $tenant = (new Tenant())->setKey('acme');
    $aware = $this->aware();

    $aware->onTenantSelectEvent(new TenantSelectEvent($tenant));

    self::assertSame($tenant, $aware->getTenant());
  }

  public function testResetClearsTheCurrentTenant(): void
  {
    $aware = $this->aware();
    $aware->onTenantSelectEvent(new TenantSelectEvent((new Tenant())->setKey('acme')));

    $aware->onTenantSelectEvent(new TenantSelectEvent(null));

    self::assertNull($aware->getTenant(false));
  }

  public function testSwitchingTenantsReplacesTheCurrentOne(): void
  {
    $second = (new Tenant())->setKey('globex');
    $aware = $this->aware();

    $aware->onTenantSelectEvent(new TenantSelectEvent((new Tenant())->setKey('acme')));
    $aware->onTenantSelectEvent(new TenantSelectEvent($second));

    self::assertSame($second, $aware->getTenant());
  }

  public function testGetTenantThrowsByDefaultWhenNothingIsSelected(): void
  {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('Tenant could not be resolved!');

    $this->aware()->getTenant();
  }

  public function testGetTenantCanReportAbsenceInsteadOfThrowing(): void
  {
    self::assertNull($this->aware()->getTenant(false));
  }

  public function testIsRegisteredAsATenantSelectListener(): void
  {
    $attributes = (new \ReflectionClass(TenantAware::class))->getAttributes(AsEventListener::class);

    self::assertCount(1, $attributes);

    $listener = $attributes[0]->newInstance();

    self::assertSame(TenantSelectEvent::class, $listener->event);
    self::assertSame('onTenantSelectEvent', $listener->method);
  }

  private function aware(): TenantAware
  {
    return new TenantAware(
      $this->createMock(EntityManagerInterface::class),
      $this->createMock(TokenStorageInterface::class),
      $this->createMock(EventDispatcherInterface::class),
    );
  }
}
