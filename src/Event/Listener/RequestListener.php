<?php

namespace aPajo\MultiTenancyBundle\Event\Listener;

use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', method: 'onKernelRequest', priority: 5)]
class RequestListener
{
  public function __construct(
    private readonly EnvironmentProvider $environmentProvider,
  )
  {
  }

  public function onKernelRequest(RequestEvent $event): void
  {
    $this->environmentProvider->init();
  }
}
