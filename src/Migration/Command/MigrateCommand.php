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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Command for migrating (all) tenant databases
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class MigrateCommand extends AbstractCommand
{
  const NAME = 'tenants:migrations:migrate';

  protected array $config;
  protected OutputInterface $output;

  public function __construct(
    private readonly MigrationManager    $migrationManager,
    private readonly TenantManager       $tenantManager,
    private readonly TenantConfig        $tenantConfig,
    private readonly KernelInterface     $kernel,
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
      ->setName(MigrateCommand::NAME)
      ->setDescription('Proxy to launch doctrine:migrations:migrate for (all) tenant databases .')
      ->addArgument('tenant', InputArgument::OPTIONAL, 'Tenant key/dentifier')
      ->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest')
      ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'Entity-manager name', 'tenant');
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $tenantKey = $input->getArgument('tenant');
    $version = $input->getArgument('version');
    $em = $input->getOption('em');

    $this->output = $output;

    if ($em === 'default') {
      $this->migrateDefaultDatabase($version);
    } elseif ($tenantKey) {
      $this->migrate($tenantKey, $version);
    } else {
      $this->migrateAll();
    }

    $output->writeln("Done!");

    return Command::SUCCESS;
  }

  protected function migrate(string $tenantKey, string $version = null): void
  {
    $this->output->writeln("Migrating tenant $tenantKey ...");
    $this->output->writeln("==================================================");

    try {
      $this->migrationManager->migrateByKey($tenantKey, $version);
    } catch (Throwable $exception) {
      $this->output->writeln("Migration failed for tenant $tenantKey: " . $exception->getMessage());
    }
  }

  protected function migrateAll(string $version = null): void
  {
    $this->runProcess($this->output, [
      'php', 'bin/console', MigrateCommand::NAME, '--em=default'
    ]);

    /** @var TenantInterface $tenant */
    foreach ($this->tenantManager->findAll() as $tenant) {
      $key = $this->tenantConfig->getTenantIdentifier($tenant);

      $this->runProcess($this->output, [
        'php', 'bin/console', MigrateCommand::NAME, $key, $version
      ]);
    }
  }

  protected function migrateDefaultDatabase(string $version = null): void
  {
    $output = new BufferedOutput();

    $output->writeln("Migrating default em ...");
    $output->writeln("==================================================");

    $newInput = new ArrayInput([
      'version' => $version ?: 'latest',
      '--dry-run' => false,
      '--all-or-nothing' => true,
      '--no-interaction' => false,
      '--em' => 'default',
      '--configuration' => $this->config['default'],
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
