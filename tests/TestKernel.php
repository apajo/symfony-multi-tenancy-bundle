<?php

namespace aPajo\MultiTenancyBundle\Tests;
use aPajo\MultiTenancyBundle\APajoMultiTenancyBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

class TestKernel extends Kernel
{
  public function registerBundles(): iterable
  {
    return [
      new FrameworkBundle(),
      new SecurityBundle(),
      new DoctrineBundle(),
      new DoctrineMigrationsBundle(),
      new APajoMultiTenancyBundle(),
    ];
  }

  public function registerContainerConfiguration(LoaderInterface $loader): void
  {
    // Load bundle-specific config
    $loader->load(__DIR__.'/../config/packages/test/config.yaml');
    $loader->load(__DIR__.'/../config/packages/test/framework.yaml');
    $loader->load(__DIR__.'/../config/packages/test/doctrine.yaml');
    $loader->load(__DIR__.'/../config/packages/test/security.yaml');
  }
}
