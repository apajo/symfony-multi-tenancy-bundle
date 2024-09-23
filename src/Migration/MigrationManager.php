<?php

namespace aPajo\MultiTenancyBundle\Migration;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrationManager
{
  protected array $config;


  public function __construct(
    private KernelInterface          $kernel,
    protected DependencyFactory      $dependencyFactory,
    private EnvironmentProvider      $environmentProvider,
  )
  {
  }

  public function migrate(TenantInterface $tenant): bool
  {
    $this->environmentProvider->for($tenant, function (TenantInterface $tenant, EntityManagerInterface $em) {
      $output = new BufferedOutput();
      $newInput = new ArrayInput([
        //'version' => 'latest',
        '--dry-run' => false,
        '--all-or-nothing'  => false,
        '--em' => 'tenant',
        '--configuration' => 'config/migrations/tenant.yml'
//        '--query-time' => $input->getOption('query-time'),
//        '--allow-no-migration' => $input->getOption('allow-no-migration'),
      ]);

      $newInput->setInteractive(false);
      $migrateCommand = new MigrateCommand($this->dependencyFactory);

      $application = new Application($this->kernel);
      $application->setAutoExit(false);
      $exitCode = $application->add($migrateCommand)->run($newInput, $output);

      if ($exitCode !== 0) {
        throw new \RuntimeException('Migration failed');
      }
    });

    return 0;
  }

  public function setMigrationConfig(array $config): void
  {
    $this->config = $config;
  }
}
