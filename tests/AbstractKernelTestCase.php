<?php

namespace aPajo\MultiTenancyBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for tests that boot the kernel.
 *
 * Booting installs Symfony's ErrorHandler as both the error and the exception
 * handler and never removes it, which PHPUnit 11 reports as a risky test.
 * Registration is guarded internally, so a boot only pushes handlers the first
 * time it happens in a process. Rather than guess, each boot compares the
 * handlers before and after and pops exactly what it pushed.
 */
abstract class AbstractKernelTestCase extends KernelTestCase
{
  private int $pushedErrorHandlers = 0;

  private int $pushedExceptionHandlers = 0;

  protected function boot(): ContainerInterface
  {
    $errorBefore = self::currentErrorHandler();
    $exceptionBefore = self::currentExceptionHandler();

    self::bootKernel();

    if (self::currentErrorHandler() !== $errorBefore) {
      ++$this->pushedErrorHandlers;
    }

    if (self::currentExceptionHandler() !== $exceptionBefore) {
      ++$this->pushedExceptionHandlers;
    }

    return static::getContainer();
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    while ($this->pushedErrorHandlers > 0) {
      restore_error_handler();
      --$this->pushedErrorHandlers;
    }

    while ($this->pushedExceptionHandlers > 0) {
      restore_exception_handler();
      --$this->pushedExceptionHandlers;
    }
  }

  /**
   * Reads the active handler without disturbing the stack: setting a handler
   * returns the previous one, and restoring immediately undoes the probe.
   */
  private static function currentErrorHandler(): mixed
  {
    $handler = set_error_handler(null);
    restore_error_handler();

    return $handler;
  }

  private static function currentExceptionHandler(): mixed
  {
    $handler = set_exception_handler(null);
    restore_exception_handler();

    return $handler;
  }
}
