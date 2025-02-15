<?php

namespace aPajo\MultiTenancyBundle\Service;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class TenantConfig
{
  function __construct(
    private array                  $config,
    private readonly EntityManagerInterface $em,
  )
  {
  }

  public function getEntityManager(): EntityManagerInterface
  {
    return $this->em;
  }

  public function getRepository(): EntityRepository
  {
    return $this->em->getRepository($this->config['tenant']['class']);
  }

  public function getTenantIdentifier(TenantInterface $tenant): string
  {
    $accessor = new PropertyAccessor();

    return $accessor->getValue(
      $tenant,
      $this->config['tenant']['identifier']
    );
  }

  public function getTenantIdentifierColumn(): string
  {
    return $this->config['tenant']['identifier'];
  }
}
