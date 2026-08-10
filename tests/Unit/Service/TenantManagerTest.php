<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use aPajo\MultiTenancyBundle\Service\Resolver\TenantResolverInterface;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Service\TenantManager;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantManager::class)]
class TenantManagerTest extends TestCase
{
  public function testResolveReturnsNullWhenNoResolversAreRegistered(): void
  {
    self::assertNull($this->manager(new ResolverRegistry())->resolve());
  }

  public function testUnsupportedResolversAreNeverAsked(): void
  {
    $resolver = $this->createMock(TenantResolverInterface::class);
    $resolver->method('supports')->willReturn(false);
    $resolver->expects($this->never())->method('resolve');

    self::assertNull($this->manager(new ResolverRegistry($resolver))->resolve());
  }

  public function testFirstResolverToProduceATenantWins(): void
  {
    $expected = (new Tenant())->setKey('acme');

    $winner = $this->resolver($expected);
    $loser = $this->createMock(TenantResolverInterface::class);
    $loser->method('supports')->willReturn(true);
    $loser->expects($this->never())->method('resolve');

    self::assertSame($expected, $this->manager(new ResolverRegistry($winner, $loser))->resolve());
  }

  public function testResolutionFallsThroughToTheNextResolver(): void
  {
    // A supporting resolver that returns null must not stop the chain.
    $expected = (new Tenant())->setKey('acme');

    $registry = new ResolverRegistry($this->resolver(null), $this->resolver($expected));

    self::assertSame($expected, $this->manager($registry)->resolve());
  }

  public function testResolveReturnsNullWhenEveryResolverDeclines(): void
  {
    $registry = new ResolverRegistry($this->resolver(null), $this->resolver(null));

    self::assertNull($this->manager($registry)->resolve());
  }

  public function testFindAllWrapsTheRepositoryResultInACollection(): void
  {
    $tenants = [(new Tenant())->setKey('acme'), (new Tenant())->setKey('globex')];

    $repository = $this->createMock(EntityRepository::class);
    $repository->expects($this->once())->method('findAll')->willReturn($tenants);

    $config = $this->createMock(TenantConfig::class);
    $config->method('getRepository')->willReturn($repository);

    $collection = $this->manager(new ResolverRegistry(), $config)->findAll();

    self::assertCount(2, $collection);
    self::assertSame($tenants, $collection->toArray());
  }

  private function resolver(?TenantInterface $tenant): TenantResolverInterface
  {
    $resolver = $this->createMock(TenantResolverInterface::class);
    $resolver->method('supports')->willReturn(true);
    $resolver->method('resolve')->willReturn($tenant);

    return $resolver;
  }

  private function manager(ResolverRegistry $registry, ?TenantConfig $config = null): TenantManager
  {
    return new TenantManager($registry, $config ?? $this->createMock(TenantConfig::class));
  }
}
