<?php

namespace aPajo\MultiTenancyBundle\Event;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;

class TenantSelectEvent
{
  public function __construct(private readonly ?TenantInterface $tenant = null)
  {
  }

  public function getTenant(): ?TenantInterface
  {
    return $this->tenant;
  }
}
