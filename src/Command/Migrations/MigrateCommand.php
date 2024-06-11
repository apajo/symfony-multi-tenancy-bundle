<?php

namespace aPajo\MultiTenancyBundle\Command\Migrations;

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
 * Command for migrating tenant databases
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class MigrateCommand extends Command
{
    protected static $defaultName = 'tenants:migrations:migrate';


    private $migrateCommand;

    public function __construct(MigrateCommand $migrateCommand)
    {
        $this->migrateCommand = $migrateCommand;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Proxy for doctrine:migrations:migrate command')
            ->setHelp('This command allows you to proxy the doctrine:migrations:migrate command')

            // Add any options or arguments needed by the original command
            ->addOption('version', null, InputOption::VALUE_OPTIONAL, 'The version to migrate to')
            ->addOption('write-sql', null, InputOption::VALUE_NONE, 'The path to output the migration SQL file instead of executing it')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run')
            ->addOption('query-time', null, InputOption::VALUE_OPTIONAL, 'Time all queries individually')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use', 'default')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if (!$application) {
            throw new \RuntimeException('Application not found');
        }

        // Create a new application instance to run the command
        $application = new Application();
        $application->add($this->migrateCommand);

        // Get the migrate command
        $command = $application->find('migrations:migrate');

        // Create a new input instance to pass to the migrate command
        $inputArgs = [
            'command' => 'migrations:migrate',
            '--version' => $input->getOption('version'),
            '--write-sql' => $input->getOption('write-sql'),
            '--dry-run' => $input->getOption('dry-run'),
            '--query-time' => $input->getOption('query-time'),
            '--em' => $input->getOption('em'),
            '--configuration' => sprintf('config/migrations/%s.yml', $input->getOption('em')),
        ];

        $migrateInput = new \Symfony\Component\Console\Input\ArrayInput($inputArgs);
        $migrateInput->setInteractive(false);

        return $command->run($migrateInput, $output);
    }
}