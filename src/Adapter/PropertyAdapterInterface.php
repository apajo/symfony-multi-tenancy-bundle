<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;

interface PropertyAdapterInterface extends AdapterInterface
{
  public function setProperty(string $property): void;

  public function adapt(TenantInterface $tenant);

  public function doAdapt(Dsn $dsn);
}
