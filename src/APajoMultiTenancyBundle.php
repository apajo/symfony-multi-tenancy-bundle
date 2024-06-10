<?php

namespace aPajo\MultiTenancyBundle;

use aPajo\MultiTenancyBundle\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class APajoMultiTenancyBundle extends Bundle
{
  public function getContainerExtension(): ?ExtensionInterface
  {
    return new Extension();
  }
}
