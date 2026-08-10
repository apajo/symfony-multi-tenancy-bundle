<?php

namespace aPajo\MultiTenancyBundle\Tests;

use aPajo\MultiTenancyBundle\APajoMultiTenancyBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Stands in for a host application.
 *
 * A Symfony bundle is not runnable on its own, so its test suite ships the
 * smallest kernel that can boot it. KernelTestCase picks this class up through
 * the KERNEL_CLASS env var set in phpunit.xml.
 */
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

  public function getProjectDir(): string
  {
    return dirname(__DIR__);
  }

  public function registerContainerConfiguration(LoaderInterface $loader): void
  {
    // Glob loading rather than an explicit list: a config file added to
    // config/packages/test/ is picked up automatically. The previous explicit
    // list silently omitted apajo_multi_tenancy.yaml, which left the required
    // `tenant.class` unset and made the kernel unbootable.
    $loader->load($this->getProjectDir() . '/config/{packages}/' . $this->environment . '/*.yaml', 'glob');
    $loader->load($this->getProjectDir() . '/config/{services}_' . $this->environment . '.yaml', 'glob');
  }

  public function getCacheDir(): string
  {
    return $this->getProjectDir() . '/var/cache/' . $this->environment;
  }

  public function getLogDir(): string
  {
    return $this->getProjectDir() . '/var/log';
  }
}
