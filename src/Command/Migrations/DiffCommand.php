<?php

namespace aPajo\MultiTenancyBundle\Command\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand as BaseCommand;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generating (tenant) database diffs
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class DiffCommand extends Command
{
  protected static $defaultName = 'tenants:migrations:diff';

  protected array $config;

  public function __construct(
    protected EntityManagerInterface $entityManager,
    protected DependencyFactory      $dependencyFactory,
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
      ->setDescription('Proxy for doctrine:migrations:diff command')
      ->setHelp('This command allows you to proxy the doctrine:migrations:diff command')

      // Add any options or arguments needed by the original command
      ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use', 'tenant')
      ->addOption('filter-expression', null, InputOption::VALUE_OPTIONAL, 'Filter expression', '')
      ->addOption('formatted', null, InputOption::VALUE_NONE, 'To output formatted code')
      ->addOption('line-length', null, InputOption::VALUE_OPTIONAL, 'Max line length of generated code', 120)
      ->addOption('check-database-platform', null, InputOption::VALUE_NONE, 'Check database platform');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $application = $this->getApplication();
    if (!$application) {
      throw new RuntimeException('Application not found');
    }

    // Create the DiffCommand
    $diffCommand = new BaseCommand($this->dependencyFactory);

    // Create a new application instance to run the command
    $application = new Application();
    $application->add($diffCommand);

    // Create a new input instance to pass to the diff command
    $inputArgs = [
      'command' => 'migrations:diff',
      '--filter-expression' => $input->getOption('filter-expression') ?: '',
      '--formatted' => $input->getOption('formatted'),
      '--line-length' => $input->getOption('line-length'),
      '--check-database-platform' => $input->getOption('check-database-platform'),

      '--no-interaction' => '',
      '--em' => $input->getOption('em'),
    ];

    if ($this->resolveNamespace($input->getOption('em'))) {
      $inputArgs['--namespace'] = $this->resolveNamespace($input->getOption('em'));
    }

    $diffInput = new ArrayInput($inputArgs);
    $diffInput->setInteractive(false);

    return $diffCommand->run($diffInput, $output);
  }

  protected function resolveNamespace(string $entityManager): ?string
  {
    if ($entityManager == $this->config['entity_manager']) {
      return $this->config['namespace'];
    }

    return null;
  }
}
