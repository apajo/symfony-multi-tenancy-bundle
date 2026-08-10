<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service;

use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantConfig::class)]
class TenantConfigTest extends TestCase
{
  private function config(array $tenant = []): array
  {
    return ['tenant' => $tenant + ['class' => Tenant::class, 'identifier' => 'key']];
  }

  public function testExposesTheInjectedEntityManager(): void
  {
    $em = $this->createMock(EntityManagerInterface::class);

    self::assertSame($em, (new TenantConfig($this->config(), $em))->getEntityManager());
  }

  public function testRepositoryIsResolvedFromTheConfiguredTenantClass(): void
  {
    $repository = $this->createMock(EntityRepository::class);

    $em = $this->createMock(EntityManagerInterface::class);
    $em->expects($this->once())
      ->method('getRepository')
      ->with(Tenant::class)
      ->willReturn($repository);

    self::assertSame($repository, (new TenantConfig($this->config(), $em))->getRepository());
  }

  public function testTenantIdentifierIsReadThroughTheConfiguredProperty(): void
  {
    $config = new TenantConfig($this->config(), $this->createMock(EntityManagerInterface::class));

    self::assertSame('acme', $config->getTenantIdentifier((new Tenant())->setKey('acme')));
  }

  public function testIdentifierPropertyIsConfigurable(): void
  {
    $config = new TenantConfig(
      $this->config(['identifier' => 'host']),
      $this->createMock(EntityManagerInterface::class)
    );

    self::assertSame('acme.example.com', $config->getTenantIdentifier(
      (new Tenant())->setKey('acme')->setHost('acme.example.com')
    ));
  }

  public function testIdentifierColumnIsReportedForQueryBuilding(): void
  {
    $em = $this->createMock(EntityManagerInterface::class);

    self::assertSame('key', (new TenantConfig($this->config(), $em))->getTenantIdentifierColumn());
    self::assertSame('host', (new TenantConfig($this->config(['identifier' => 'host']), $em))->getTenantIdentifierColumn());
  }
}
