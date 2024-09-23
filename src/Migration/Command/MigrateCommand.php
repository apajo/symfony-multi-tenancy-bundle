<?php

namespace aPajo\MultiTenancyBundle\Migration\Command;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Service\TenantManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for migrating all tenant databases
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class MigrateCommand extends Command
{
  protected array $config;

  public function __construct(
    private MigrationManager $migrationManager,
    private TenantManager $tenantManager,
    private TenantConfig $tenantConfig
  )
  {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->setName('tenants:migrations:migrate')
      ->setDescription('Proxy to launch doctrine:migrations:migrate for (all) tenant databases .')
      ->addArgument('tenant_id', InputArgument::OPTIONAL, 'Tenant key/dentifier', null)
      ->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
      ->addOption('query-time', null, InputOption::VALUE_NONE, 'Time all the queries individually.')
      ->addOption('allow-no-migration', null, InputOption::VALUE_NONE, 'Do not throw an exception when no changes are detected.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $tenantKey = $input->getArgument('tenant_id');

    /** @var TenantInterface $tenant */
    foreach ($this->tenantManager->findAll() as $tenant) {
      $id = $this->tenantConfig->getTenantIdentifier($tenant);

      if ($tenantKey !== $id && $tenantKey !== null) {
        continue;
      }

      $output->writeln("Migrating tenant {$id} ...");
      $output->writeln("==================================================");

      try {
        $this->migrationManager->migrate($tenant);
      } catch (\Throwable $exception) {
        $output->writeln("Migration failed for tenant {$id}: " . $exception->getMessage());
      }


    }

    $output->writeln("Done!");

    return 0;
  }


}
