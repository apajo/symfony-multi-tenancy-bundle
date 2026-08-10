<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service\Registry;

use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use aPajo\MultiTenancyBundle\Service\Resolver\TenantResolverInterface;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResolverRegistry::class)]
class ResolverRegistryTest extends TestCase
{
  public function testEmptyRegistryYieldsAnEmptyCollection(): void
  {
    $registry = new ResolverRegistry();

    self::assertInstanceOf(Collection::class, $registry->getResolvers());
    self::assertCount(0, $registry->getResolvers());
  }

  public function testResolverOrderIsPreserved(): void
  {
    // Order is behaviour, not incidental: TenantManager::resolve() returns the
    // first resolver that produces a tenant.
    $first = $this->createMock(TenantResolverInterface::class);
    $second = $this->createMock(TenantResolverInterface::class);

    $registry = new ResolverRegistry($first, $second);

    self::assertSame([$first, $second], $registry->getResolvers()->toArray());
  }

  public function testNonResolverArgumentIsRejected(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Resolver must implement ' . TenantResolverInterface::class);

    new ResolverRegistry(new \stdClass());
  }
}
