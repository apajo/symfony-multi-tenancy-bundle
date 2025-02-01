<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait PropertyAdapterTrait
{
  public function setProperty(string $property): void
  {
    $this->property = $property;
  }

  public function adapt(TenantInterface $tenant)
  {
    $accessor = new PropertyAccessor();
    $dsn = $accessor->getValue($tenant, $this->property);

    if (!$dsn) {
      return null;
    }

    return $this->doAdapt(Dsn::fromString($dsn));
  }

}
