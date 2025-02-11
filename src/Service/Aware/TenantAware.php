<?php

namespace aPajo\MultiTenancyBundle\Service\Aware;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: TenantSelectEvent::class, method: 'onTenantSelectEvent')]
class TenantAware
{

  private ?TenantInterface $tenant = null;

  public function __construct(
    EntityManagerInterface                    $em,
    TokenStorageInterface                     $tokenStorage,
    protected SymfonyEventDispatcherInterface $dispatcher,
  )
  {
  }

  public function onTenantSelectEvent(TenantSelectEvent $event): void
  {
    $tenant = $event->getTenant();
    $this->tenant = $tenant;
  }

  public function getTenant($exception = true): ?TenantInterface
  {
    if (!$this->tenant && $exception) {
      throw new LogicException('Tenant could not be resolved!');
    }

    return $this->tenant;
  }
}
