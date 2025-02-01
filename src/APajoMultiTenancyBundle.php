<?php

namespace aPajo\MultiTenancyBundle;

use aPajo\MultiTenancyBundle\DependencyInjection\Compiler\MigrationsPathsCompilerPass;
use aPajo\MultiTenancyBundle\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class APajoMultiTenancyBundle extends AbstractBundle
{
  public function build(ContainerBuilder $container)
  {
    parent::build($container);

    $container->addCompilerPass(new MigrationsPathsCompilerPass());
  }

  public function getContainerExtension(): ?ExtensionInterface
  {
    return new Extension();
  }
}
