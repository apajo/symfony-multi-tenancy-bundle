<?php

namespace aPajo\MultiTenancyBundle\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AbstractCommand extends Command
{
  protected function runProcess(OutputInterface $output, array $command): void
  {
    $process = new Process($command);
    $process->setTimeout(300); // Set timeout to 5 minutes
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $output->writeln($process->getOutput());
  }
}
