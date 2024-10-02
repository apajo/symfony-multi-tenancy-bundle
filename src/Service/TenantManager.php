<?php

namespace aPajo\MultiTenancyBundle\Service;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

class TenantManager
{
  public function __construct(
    private ResolverRegistry         $resolverRegistry,
    private TenantConfig             $config,
  )
  {
  }

  public function resolve(): ?TenantInterface
  {
    foreach ($this->resolverRegistry->getResolvers() as $resolver) {
      if (!$resolver->supports()) {
        continue;
      }

      $tenant = $resolver->resolve();

      if ($tenant) {
        return $tenant;
      }
    }

    return null;
  }

  public function findAll(): Collection
  {
    return new ArrayCollection($this->getRepo()->findAll());
  }

  private function getRepo(): EntityRepository
  {
    return $this->config->getRepository();
  }
}
