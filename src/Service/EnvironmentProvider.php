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
use Monolog\Logger;

#[AsEventListener(event: TenantSelectEvent::class, method: 'onTenantSelectEvent')]
class EnvironmentProvider
{
  protected ?Logger $logger;

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
    $tenants->map(function (TenantInterface $tenant) use ($callback) {
      try {
        $this->for($tenant, $callback);
      } catch (\Throwable $e) {
        //throw $e;
      }
    });
  }

  public function setLogger(Logger $logger): void
  {
    $this->logger = $logger;
  }

  /**
   * @param $callback
   */
  public function forAll($callback): void
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

  public function for(TenantInterface $tenant, $callback)
  {
    $this->select($tenant);

    // Transaction gives error: There is no active transaction
    // $this->tenantEm->wrapInTransaction(function (EntityManagerInterface $em) use ($callback, $tenant) {
    call_user_func($callback, $tenant, $this->tenantConfig->getEntityManager());
    // });

    $this->reset();
  }

  public function select(?TenantInterface $tenant = null)
  {
    $event = new TenantSelectEvent($tenant);
    $this->dispatcher->dispatch($event);
  }

  public function reset()
  {
    $event = new TenantSelectEvent(null);
    $this->dispatcher->dispatch($event);
  }

  public function selectTenantById(?int $tenantId = null)
  {
    $tenant = $this->getRepo()->find($tenantId);
    $event = new TenantSelectEvent($tenant);
    $this->dispatcher->dispatch($event);
  }

  public function init()
  {
    $tenant = $this->tenantManager->resolve();

    $event = new TenantSelectEvent($tenant);
    $this->dispatcher->dispatch($event);
  }

  public function onTenantSelectEvent(TenantSelectEvent $event)
  {
    $tenant = $event->getTenant();

    // TODO: implement null-tenant handling
    if (!$tenant instanceof TenantInterface) {
      return;
    }

    $this->log('Selecting tenant: ' . $tenant?->getId(), Logger::DEBUG);
    $this->adapterRegistry->getAdapters()->map(function (AdapterInterface $adapter) use ($tenant) {
      $this->log('Applying adapter: ' . $adapter::class, Logger::DEBUG);

      $adapter->adapt($tenant);
    });

    $this->log('Selected tenant: ' . $tenant?->getId());
  }

  protected function log(string $message, $level = null): void
  {
    if (!$this->logger) {
      return;
    }

    $level = $level ?: Logger::ALERT;

    $this->logger->log($level, 'TenantBundle: ' . $message);
  }
}
