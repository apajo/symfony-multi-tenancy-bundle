<?php

namespace aPajo\MultiTenancyBundle\Event;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;

class TenantSelectEvent
{
  private ?TenantInterface $tenant = null;

  public function __construct(?TenantInterface $tenant = null)
  {
    $this->tenant = $tenant;
  }

  public function getTenant(): ?TenantInterface
  {
    return $this->tenant;
  }
}
