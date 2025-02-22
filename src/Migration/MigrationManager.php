<?php

namespace aPajo\MultiTenancyBundle\Migration;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrationManager
{
  protected array $config;


  public function __construct(
    private readonly KernelInterface     $kernel,
    protected DependencyFactory $dependencyFactory,
    private readonly EnvironmentProvider $environmentProvider,
    private readonly TenantConfig        $tenantConfig,
  )
  {
  }

  public function migrateByKey(string $key): bool
  {
    $col = $this->tenantConfig->getTenantIdentifierColumn();
    $repo = $this->tenantConfig->getRepository();

    $crits = [];
    $crits[$col] = $key;

    return $this->migrate(
      $repo->findOneBy($crits)
    );
  }

  public function migrate(TenantInterface $tenant, string $version = null): bool
  {
    $this->environmentProvider->for($tenant, function (TenantInterface $tenant, EntityManagerInterface $em) use ($version): void {
      $output = new BufferedOutput();
      $newInput = new ArrayInput([
        'version' => $version ?: 'latest',
        '--dry-run' => false,
        '--all-or-nothing' => true,
        '--no-interaction' => false,
        '--em' => 'tenant',
        '--configuration' => $this->config['tenant'],
      ]);

      $newInput->setInteractive(false);
      $migrateCommand = new MigrateCommand($this->dependencyFactory);

      $application = new Application($this->kernel);
      $application->setAutoExit(false);
      $exitCode = $application->add($migrateCommand)->run($newInput, $output);

      if ($exitCode !== 0) {
        throw new RuntimeException('Migration failed');
      }
    });

    return 0;
  }

  public function setMigrationConfig(array $config): void
  {
    $this->config = $config;
  }
}
