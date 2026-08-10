<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Event\Listener;

use aPajo\MultiTenancyBundle\Event\Listener\RequestListener;
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(RequestListener::class)]
class RequestListenerTest extends TestCase
{
  public function testKernelRequestTriggersTenantInitialisation(): void
  {
    $provider = $this->createMock(EnvironmentProvider::class);
    $provider->expects($this->once())->method('init');

    (new RequestListener($provider))->onKernelRequest($this->event());
  }

  public function testSubRequestsAlsoInitialise(): void
  {
    // The listener does not filter on request type; this pins the current
    // contract so a future isMainRequest() guard is a deliberate change.
    $provider = $this->createMock(EnvironmentProvider::class);
    $provider->expects($this->once())->method('init');

    (new RequestListener($provider))->onKernelRequest($this->event(HttpKernelInterface::SUB_REQUEST));
  }

  public function testIsRegisteredOnKernelRequest(): void
  {
    $attributes = (new \ReflectionClass(RequestListener::class))
      ->getAttributes(AsEventListener::class);

    self::assertCount(1, $attributes);

    $listener = $attributes[0]->newInstance();

    self::assertSame('kernel.request', $listener->event);
    self::assertSame('onKernelRequest', $listener->method);
    self::assertSame(5, $listener->priority);
  }

  private function event(int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
  {
    return new RequestEvent(
      $this->createMock(HttpKernelInterface::class),
      Request::create('https://acme.example.com/'),
      $type
    );
  }
}
