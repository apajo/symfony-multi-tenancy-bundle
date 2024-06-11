<?php

namespace aPajo\MultiTenancyBundle\Service\Resolver;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;

interface TenantResolverInterface
{
  public function resolve(): ?TenantInterface;

  public function supports(): bool;
}
