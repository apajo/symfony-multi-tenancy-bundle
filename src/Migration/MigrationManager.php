<?php

namespace aPajo\MultiTenancyBundle\Migration;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrationManager
{
  protected array $config;


  public function __construct(
    private KernelInterface          $kernel,
    protected DependencyFactory      $dependencyFactory,
  )
  {
  }

  public function migrate(TenantInterface $tenant): bool
  {
    dump($tenant);
    return true;
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
//      'dbId' => $tenant->getKey(),
      'version' => $input->getArgument('version'),
      '--dry-run' => $input->getOption('dry-run'),
      '--query-time' => $input->getOption('query-time'),
      '--allow-no-migration' => $input->getOption('allow-no-migration'),
    ], new InputDefinition([
      new InputArgument('command', InputArgument::OPTIONAL),
//      new InputArgument('dbId', InputArgument::OPTIONAL),
      new InputArgument('version', InputArgument::OPTIONAL),
      new InputOption('dry-run', null, InputOption::VALUE_NONE),
      new InputOption('query-time', null, InputOption::VALUE_NONE),
      new InputOption('allow-no-migration', null, InputOption::VALUE_NONE),
    ]));
    $newInput->setInteractive(false);

    $application = new Application($this->kernel);
    $application->setAutoExit(true);

    $migrateCommand = new MigrateCommand(
      $this->dependencyFactory
    );

    $exitCode = $application->add($migrateCommand)->run($newInput, $output);

    return $exitCode;
  }

  public function setMigrationConfig(array $config): void
  {
    $this->config = $config;
  }
}
