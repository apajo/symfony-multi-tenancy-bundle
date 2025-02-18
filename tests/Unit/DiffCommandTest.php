<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit;


class DiffCommandTest extends AbstractTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    self::bootKernel();
  }

  /**
   * @covers \aPajo\MultiTenancyBundle\Migration\Command\DiffCommand
   */
  public function testDiffCommand()
  {
    $this->runProcess(['php', 'bin/console', '--env=test', 'tenants:migrations:diff', '-vvv']);
  }
}
