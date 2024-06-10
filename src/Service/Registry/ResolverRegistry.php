<?php

namespace aPajo\MultiTenancyBundle\Service\Registry;

use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use aPajo\MultiTenancyBundle\Service\Resolver\TenantResolverInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ResolverRegistry
{
  /**
   * @var TenantResolverInterface[]|Collection|null
   */
  private ?Collection $adapters = null;

  function __construct(...$adapters)
  {
    $this->adapters = new ArrayCollection();

    foreach ($adapters as $adapter) {
      if (!($adapter instanceof TenantResolverInterface)) {
        throw new InvalidArgumentException('Resolver must implement ' . TenantResolverInterface::class);
      }

      $this->adapters->add($adapter);
    }
  }

  /**
   * @return TenantResolverInterface[]|Collection|null
   */
  public function getResolvers(): Collection
  {
    return $this->adapters;
  }
}
