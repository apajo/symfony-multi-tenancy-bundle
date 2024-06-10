<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;

interface AdapterInterface
{
  public function adapt(TenantInterface $tenant);
}
