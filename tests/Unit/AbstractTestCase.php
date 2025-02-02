<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AbstractTestCase extends KernelTestCase
{
  protected function buildContainer(): void
  {
    $container = new ContainerBuilder();

    $mockDoctrine = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
    $container->setDefinition('doctrine', new Definition(get_class($mockDoctrine)));
    $container->set('doctrine', $mockDoctrine);

    $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $container->setDefinition('event_dispatcher', new Definition(get_class($mockEventDispatcher)));
    $container->set('event_dispatcher', $mockEventDispatcher);

    
    $databaseAdapter = new DatabaseAdapter($container->get('doctrine'));
    $container->set('aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter', $databaseAdapter);
  }

  protected function output(): OutputInterface
  {
    // Create a mock for the OutputInterface
    $output = $this->createMock(OutputInterface::class);
    $output->expects($this->once())
      ->method('writeln')
      ->with($this->isType('string'));

    return $output;
  }

  protected function runProcess(array $command): void
  {
    $process = new Process($command);
    $process->setTimeout(300); // Set timeout to 5 minutes
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    // Directly print the process output
    echo $process->getOutput();

    $this->output()->writeln($process->getOutput());
  }
}
