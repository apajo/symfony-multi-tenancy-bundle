<?php

namespace aPajo\MultiTenancyBundle\Command\Migrations;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand as BaseCommand;
use Symfony\Component\Console\Application;
use Doctrine\ORM\EntityManagerInterface;


/**
 * Command for generating (tenant) database diffs
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class DiffCommand extends Command
{
    protected static $defaultName = 'tenants:migrations:diff';

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Proxy for doctrine:migrations:diff command')
            ->setHelp('This command allows you to proxy the doctrine:migrations:diff command')
            // Add any options or arguments needed by the original command
            ->addOption('filter-expression', null, InputOption::VALUE_OPTIONAL, 'Filter expression')
            ->addOption('formatted', null, InputOption::VALUE_NONE, 'To output formatted code')
            ->addOption('line-length', null, InputOption::VALUE_OPTIONAL, 'Max line length of generated code')
            ->addOption('check-database-platform', null, InputOption::VALUE_NONE, 'Check database platform')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if (!$application) {
            throw new \RuntimeException('Application not found');
        }

        // Set the entity manager
        $emName = $input->getOption('em');
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager($emName);

        // Create the DiffCommand
        $diffCommand = new DiffCommand();

        // Create a new application instance to run the command
        $application = new Application();
        $application->add($diffCommand);

        // Get the diff command
        $command = $application->find('migrations:diff');

        // Create a new input instance to pass to the diff command
        $inputArgs = [
            'command' => 'migrations:diff',
            '--filter-expression' => $input->getOption('filter-expression'),
            '--formatted' => $input->getOption('formatted'),
            '--line-length' => $input->getOption('line-length'),
            '--check-database-platform' => $input->getOption('check-database-platform'),

            '--no-interaction' => '',
            '--em' => $input->getOption('em'),
            // '--namespace' => sprintf('config/migrations/%s.yml', $input->getOption('em')),
        ];

        $diffInput = new \Symfony\Component\Console\Input\ArrayInput($inputArgs);
        $diffInput->setInteractive(false);

        return $command->run($diffInput, $output);
    }
}
