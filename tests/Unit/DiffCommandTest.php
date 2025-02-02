<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit;


class DiffCommandTest extends AbstractTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    self::bootKernel();
    $this->container = self::$container;
  }
  
  /**
   * @covers \aPajo\MultiTenancyBundle\Migration\Command\DiffCommand
   */
  public function testRunProcess()
  {
    $this->runProcess(['ls', '-lah']);
  }

  /**
   * @covers \aPajo\MultiTenancyBundle\Migration\Command\DiffCommand
   */
  public function testDiffCommand()
  {
    $this->runProcess(['php', 'bin/console', 'tenants:migrations:diff']);
  }
}
