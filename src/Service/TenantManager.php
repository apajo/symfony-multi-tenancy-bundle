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
    private KernelInterface          $kernel,
    private ManagerRegistry          $registry,
    private ContainerInterface       $container,
    private EventDispatcherInterface $eventDispatcher,
  )
  {
  }

  public function migrate(TenantInterface $tenant): bool
  {
    $output = new BufferedOutput();

    $exitCode = $this->migrateWithIO($tenant, new ArrayInput([
      'version' => 'latest',
      '--dry-run' => false,
      '--query-time' => false,
      '--allow-no-migration' => true,
    ], new InputDefinition([
      new InputArgument('version', InputArgument::OPTIONAL),
      new InputOption('dry-run', null, InputOption::VALUE_NONE),
      new InputOption('query-time', null, InputOption::VALUE_NONE),
      new InputOption('allow-no-migration', null, InputOption::VALUE_NONE),
    ])), $output);

    return $exitCode === 0;
  }

  public function migrateWithIO(TenantInterface $tenant, InputInterface $input, OutputInterface $output = null): int
  {
    $newInput = new ArrayInput([
      'command' => 'tenant:migrations:migrate',
      'dbId' => $tenant->getKey(),
      'version' => $input->getArgument('version'),
      '--dry-run' => $input->getOption('dry-run'),
      '--query-time' => $input->getOption('query-time'),
      '--allow-no-migration' => $input->getOption('allow-no-migration'),
    ], new InputDefinition([
      new InputArgument('command', InputArgument::OPTIONAL),
      new InputArgument('dbId', InputArgument::OPTIONAL),
      new InputArgument('version', InputArgument::OPTIONAL),
      new InputOption('dry-run', null, InputOption::VALUE_NONE),
      new InputOption('query-time', null, InputOption::VALUE_NONE),
      new InputOption('allow-no-migration', null, InputOption::VALUE_NONE),
    ]));
    $newInput->setInteractive(false);

    $application = new Application($this->kernel);
    $application->setAutoExit(true);

    $migrateCommand = new HakamMigrateCommand(
      $this->registry,
      $this->container,
      $this->eventDispatcher
    );

    $exitCode = $application->add($migrateCommand)->run($newInput, $output);

    return $exitCode;
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
