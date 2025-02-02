<?php

namespace aPajo\MultiTenancyBundle\Migration\Command;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Service\TenantManager;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand as DoctrineMigrateCommand;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use Symfony\Component\Console\Command\Command;

/**
 * Command for migrating (all) tenant databases
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class MigrateCommand extends AbstractCommand
{
  protected array $config;

  public function __construct(
    private MigrationManager    $migrationManager,
    private TenantManager       $tenantManager,
    private TenantConfig        $tenantConfig,
    private KernelInterface     $kernel,
    protected DependencyFactory $dependencyFactory,
  )
  {
    parent::__construct();
  }

  public function setMigrationConfig(array $config): void
  {
    $this->config = $config;
  }

  protected function configure(): void
  {
    $this
      ->setName('tenants:migrations:migrate')
      ->setDescription('Proxy to launch doctrine:migrations:migrate for (all) tenant databases .')
      ->addArgument('tenant_id', InputArgument::OPTIONAL, 'Tenant key/dentifier')
      ->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
      ->addOption('query-time', null, InputOption::VALUE_NONE, 'Time all the queries individually.')
      ->addOption('allow-no-migration', null, InputOption::VALUE_NONE, 'Do not throw an exception when no changes are detected.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $tenantKey = $input->getArgument('tenant_id');
    $version = $input->getArgument('version');

    $this->migrateDefaultDatabase($version);

    /** @var TenantInterface $tenant */
    foreach ($this->tenantManager->findAll() as $tenant) {
      $id = $this->tenantConfig->getTenantIdentifier($tenant);

      if ($tenantKey !== $id && $tenantKey !== null) {
        continue;
      }

      $output->writeln("Migrating tenant $id ...");
      $output->writeln("==================================================");

      try {
        $this->migrationManager->migrate($tenant, $version);
      } catch (Throwable $exception) {
        $output->writeln("Migration failed for tenant $id: " . $exception->getMessage());
      }
    }

    $output->writeln("Done!");

    return Command::SUCCESS;
  }

  protected function migrateDefaultDatabase(string $version = null): void
  {
    $output = new BufferedOutput();
    $newInput = new ArrayInput([
      'version' => $version ?: 'latest',
      '--dry-run' => false,
      '--all-or-nothing' => true,
      '--no-interaction' => true,
      '--em' => 'default',
      '--configuration' => 'vendor/apajo/symfony-multi-tenancy-bundle/config/migrations/default.yml'
    ]);

    $newInput->setInteractive(false);
    $migrateCommand = new DoctrineMigrateCommand($this->dependencyFactory);

    $application = new Application($this->kernel);
    $application->setAutoExit(false);
    $exitCode = $application->add($migrateCommand)->run($newInput, $output);

    if ($exitCode !== 0) {
      throw new RuntimeException('Migration failed');
    }
  }
}
