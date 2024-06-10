<?php

namespace aPajo\MultiTenancyBundle\Command;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Service\TenantManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
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

  public function __construct(
    private TenantManager $tenantManager,
    private EnvironmentProvider $environmentProvider,
    private TenantConfig $tenantConfig,
    private EntityManagerInterface $em
  )
  {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->setName('tenants:migrations:migrate:all')
      ->setAliases(['t:m:m:a'])
      ->setDescription('Proxy to launch doctrine:migrations:migrate for all tenant databases .')
      ->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
      ->addOption('query-time', null, InputOption::VALUE_NONE, 'Time all the queries individually.')
      ->addOption('allow-no-migration', null, InputOption::VALUE_NONE, 'Do not throw an exception when no changes are detected.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $total = $this->tenantManager->findAll()->count();
    $output->writeln("Total of {$total} tenants found.");



    $this->environmentProvider->forAll(function (TenantInterface $tenant, EntityManagerInterface $em ) use ($input, $output,$total) {
      $id = $this->tenantConfig->getTenantIdentifier($tenant);
      $output->writeln("Migrating tenant {$id} ...");
      $output->writeln("==================================================");

      $this->migrate($input, $output);
    });


    $output->writeln("Done!");

    return 0;
  }

  protected function migrate(InputInterface $input, OutputInterface $output): int
  {
    $newInput = new ArrayInput([
      'version' => $input->getArgument('version'),
      '--dry-run' => $input->getOption('dry-run'),
      '--query-time' => $input->getOption('query-time'),
      '--allow-no-migration' => $input->getOption('allow-no-migration'),
    ]);

    $newInput->setInteractive($input->isInteractive());

    $tenantMigrationConfig = new ConfigurationArray([]);

    $depFactory = DependencyFactory::fromEntityManager(
      $tenantMigrationConfig,
      new ExistingEntityManager($this->em)
    );

    $otherCommand = new \Doctrine\Migrations\Tools\Console\Command\MigrateCommand($depFactory);
    return $otherCommand->run($newInput, $output);
  }

}
