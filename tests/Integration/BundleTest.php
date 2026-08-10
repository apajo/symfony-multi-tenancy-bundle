<?php

namespace aPajo\MultiTenancyBundle\Tests\Integration;

use aPajo\MultiTenancyBundle\APajoMultiTenancyBundle;
use aPajo\MultiTenancyBundle\DependencyInjection\Compiler\MigrationsPathsCompilerPass;
use aPajo\MultiTenancyBundle\DependencyInjection\Extension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(APajoMultiTenancyBundle::class)]
class BundleTest extends TestCase
{
  public function testProvidesItsOwnExtension(): void
  {
    $extension = (new APajoMultiTenancyBundle())->getContainerExtension();

    self::assertInstanceOf(Extension::class, $extension);
    self::assertSame('apajo_multi_tenancy', $extension->getAlias());
  }

  public function testRegistersTheMigrationsPathsCompilerPass(): void
  {
    $container = new ContainerBuilder();

    (new APajoMultiTenancyBundle())->build($container);

    $passes = $container->getCompilerPassConfig()->getPasses();
    $found = array_filter($passes, static fn ($pass): bool => $pass instanceof MigrationsPathsCompilerPass);

    self::assertCount(1, $found);
  }
}
