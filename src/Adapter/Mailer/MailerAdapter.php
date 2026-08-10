<?php

namespace aPajo\MultiTenancyBundle\Adapter\Mailer;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterInterface;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterTrait;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn as MailerDsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Swaps the mailer transport based on the tenant.
 *
 * The adapter is both the transport factory and the transport itself: create()
 * hands the Mailer this object instead of a concrete transport, so that every
 * later adapt()/reset() takes effect on the transport the Mailer sends through.
 * Returning the concrete transport would freeze the Mailer on whichever one
 * happened to be current when it was first built.
 */
class MailerAdapter extends AbstractTransportFactory implements PropertyAdapterInterface, TransportInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'mailer';

  protected ?TransportInterface $transport = null;

  /**
   * Transport built from the framework-configured DSN, reverted to on reset().
   */
  protected ?TransportInterface $defaultTransport = null;

  public function create(MailerDsn $dsn): TransportInterface
  {
    $this->defaultTransport = $this->build(new Dsn(
      $dsn->getScheme(),
      $dsn->getHost(),
      $dsn->getUser(),
      $dsn->getPassword(),
      $dsn->getPort()
    ));

    // A tenant may already have been selected before the Mailer built its
    // transport; in that case its configuration stays in place.
    $this->transport ??= $this->defaultTransport;

    return $this;
  }

  public function doAdapt(Dsn $dsn)
  {
    return $this->transport = $this->build($dsn);
  }

  public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
  {
    return $this->transport()->send($message, $envelope);
  }

  public function __toString(): string
  {
    return (string) $this->transport();
  }

  protected function captureDefault(): mixed
  {
    return $this->defaultTransport;
  }

  protected function restoreDefault(mixed $default): mixed
  {
    // Null when the Mailer had not built its transport yet at the time of the
    // first adapt(); create() fills defaultTransport in afterwards.
    return $this->transport = $default ?? $this->defaultTransport;
  }

  protected function transport(): TransportInterface
  {
    if (!$this->transport instanceof TransportInterface) {
      throw new LogicException('No mailer transport has been configured.');
    }

    return $this->transport;
  }

  private function build(Dsn $dsn): TransportInterface
  {
    $scheme = $dsn->getScheme();

    if (empty($scheme)) {
      throw new InvalidArgumentException('The mailer DSN must contain a scheme.');
    }

    // Transport::fromDsn() builds its own chain of the built-in factories, so
    // this does not recurse back into this factory.
    return Transport::fromDsn((string) $dsn);
  }

  protected function getSupportedSchemes(): array
  {
    return ['smtp', 'null']; // Define a custom scheme that this factory handles
  }
}
