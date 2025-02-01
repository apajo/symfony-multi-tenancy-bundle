<?php

namespace aPajo\MultiTenancyBundle\Migration\Command;

use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Command for generating (tenant) database diffs
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class DiffCommand extends AbstractCommand
{
  protected static $defaultName = 'tenants:migrations:diff';

  protected array $config;

  public function __construct(
    protected EntityManagerInterface $entityManager,
    protected DependencyFactory      $dependencyFactory,
    protected ExistingConfiguration  $configLoader,
  )
  {
    parent::__construct();
  }

  public function setMigrationConfig(array $config): void
  {
    $this->config = $config;
  }

  protected function configure()
  {
    $this
      ->setName(self::$defaultName)
      ->setDescription('Proxy for doctrine:migrations:diff command')
      ->setHelp('This command allows you to proxy the doctrine:migrations:diff command');
  }


  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $commands = [
      ['php', 'bin/console', 'doctrine:migrations:diff', '--allow-empty-diff', '--no-interaction', '--em=default', '--namespace=App\Migrations\Default'],
      ['php', 'bin/console', 'doctrine:migrations:diff', '--allow-empty-diff', '--no-interaction', '--em=tenant', '--namespace=App\Migrations\Tenant'],
    ];

    foreach ($commands as $command) {
      $this->runProcess($output, $command);
    }

    return Command::SUCCESS;
  }

}
