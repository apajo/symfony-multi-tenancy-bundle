<?php

namespace aPajo\MultiTenancyBundle\Service\Aware;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use LogicException;
use Symfony\Contracts\Service\Attribute\Required;

trait TenantAwareTrait
{
  protected TenantAware $tenantAware;

  #[Required]
  public function setTenantAware(TenantAware $tenantAware): void
  {
    $this->tenantAware = $tenantAware;
  }

  protected function getTenant($exception = true): ?TenantInterface
  {
    $tenant = $this->tenantAware->getTenant($exception);

    if (!$tenant && $exception) {
      throw new LogicException('Tenant could not be resolved!');
    }

    return $tenant;
  }

}
