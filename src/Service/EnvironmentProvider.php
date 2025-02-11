<?php

namespace aPajo\MultiTenancyBundle\Service;

use aPajo\MultiTenancyBundle\Adapter\AdapterInterface;
use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use aPajo\MultiTenancyBundle\Service\Aware\TenantAware;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

#[AsEventListener(event: TenantSelectEvent::class, method: 'onTenantSelectEvent')]
class EnvironmentProvider
{
  public function __construct(
    protected SymfonyEventDispatcherInterface $dispatcher,
    protected TenantAware                     $tenantAware,
    protected AdapterRegistry                 $adapterRegistry,
    protected TenantManager                   $tenantManager,
    protected TenantConfig                    $tenantConfig,
  )
  {
  }

  /**
   * @param TenantInterface[]|Collection $tenants
   */
  public function forEach(Collection $tenants, callable $callback): void
  {
    $tenants->forAll(function (int $index, TenantInterface $tenant) use ($callback): void {
      $this->for($tenant, $callback);
    });
  }

  /**
   * @param $callback
   */
  public function forAll(callable $callback): void
  {
    $tenants = $this->findAll();
    $this->forEach($tenants, $callback);
  }

  protected function findAll(): Collection
  {
    return new ArrayCollection($this->getRepo()->findAll());
  }

  private function getRepo(): EntityRepository
  {
    return $this->tenantConfig->getRepository();
  }

  public function for(TenantInterface $tenant, $callback): void
  {
    $this->select($tenant);

    // Transaction gives error: There is no active transaction
    // $this->tenantEm->wrapInTransaction(function (EntityManagerInterface $em) use ($callback, $tenant) {
    call_user_func($callback, $tenant, $this->tenantConfig->getEntityManager());
    // });

    $this->reset();
  }

  public function select(?TenantInterface $tenant = null): void
  {
    $event = new TenantSelectEvent($tenant);
    $this->dispatcher->dispatch($event);
  }

  public function reset(): void
  {
    $event = new TenantSelectEvent(null);
    $this->dispatcher->dispatch($event);
  }

  public function selectTenantById(?int $tenantId = null): void
  {
    $tenant = $this->getRepo()->find($tenantId);
    $event = new TenantSelectEvent($tenant);
    $this->dispatcher->dispatch($event);
  }

  public function init(): void
  {
    $tenant = $this->tenantManager->resolve();

    $event = new TenantSelectEvent($tenant);
    $this->dispatcher->dispatch($event);
  }

  public function onTenantSelectEvent(TenantSelectEvent $event): void
  {
    $tenant = $event->getTenant();

    // TODO: implement null-tenant handling
    if (!$tenant instanceof TenantInterface) {
      return;
    }

    $this->adapterRegistry->getAdapters()->map(function (AdapterInterface $adapter) use ($tenant): void {
      $adapter->adapt($tenant);
    });
  }
}
