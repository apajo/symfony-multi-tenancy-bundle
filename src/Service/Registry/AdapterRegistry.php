<?php

namespace aPajo\MultiTenancyBundle\Service\Registry;

use aPajo\MultiTenancyBundle\Adapter\AdapterInterface;
use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class AdapterRegistry
{
  /**
   * @var AdapterInterface[]|Collection|null
   */
  private ?Collection $adapters = null;

  function __construct(...$adapters)
  {
    $this->adapters = new ArrayCollection();

    foreach ($adapters as $adapter) {
      if (!($adapter instanceof AdapterInterface)) {
        throw new InvalidArgumentException('Adapter must implement ' . AdapterInterface::class);
      }

      $this->adapters->add($adapter);
    }
  }

  /**
   * @return AdapterInterface[]|Collection|null
   */
  public function getAdapters(): Collection
  {
    return $this->adapters;
  }
}
