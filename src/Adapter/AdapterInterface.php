<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;

interface AdapterInterface
{
  public function adapt(TenantInterface $tenant);

  /**
   * Revert to the configuration the adapter was constructed with.
   *
   * Called whenever no tenant is selected (null tenant).
   */
  public function reset();
}
