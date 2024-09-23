<?php

namespace aPajo\MultiTenancyBundle\Service;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\Registry\ResolverRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Hakam\MultiTenancyBundle\Command\MigrateCommand as HakamMigrateCommand;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;

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
