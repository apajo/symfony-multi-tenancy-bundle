<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service\Registry;

use aPajo\MultiTenancyBundle\Adapter\AdapterInterface;
use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterRegistry::class)]
class AdapterRegistryTest extends TestCase
{
  public function testEmptyRegistryYieldsAnEmptyCollection(): void
  {
    $registry = new AdapterRegistry();

    self::assertInstanceOf(Collection::class, $registry->getAdapters());
    self::assertCount(0, $registry->getAdapters());
  }

  public function testAdaptersArePreservedInRegistrationOrder(): void
  {
    $first = $this->createMock(AdapterInterface::class);
    $second = $this->createMock(AdapterInterface::class);

    $registry = new AdapterRegistry($first, $second);

    self::assertCount(2, $registry->getAdapters());
    self::assertSame([$first, $second], $registry->getAdapters()->toArray());
  }

  public function testNonAdapterArgumentIsRejected(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Adapter must implement ' . AdapterInterface::class);

    new AdapterRegistry(new \stdClass());
  }

  public function testAValidAdapterDoesNotRescueAnInvalidOne(): void
  {
    $this->expectException(InvalidArgumentException::class);

    new AdapterRegistry($this->createMock(AdapterInterface::class), 'not-an-adapter');
  }
}
