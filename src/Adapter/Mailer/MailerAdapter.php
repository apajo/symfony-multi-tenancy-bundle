<?php

namespace aPajo\MultiTenancyBundle\Adapter\Mailer;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterInterface;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterTrait;
use InvalidArgumentException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn as MailerDsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Throwable;

class MailerAdapter extends AbstractTransportFactory implements PropertyAdapterInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'mailer';

  protected $transport;

  public function __construct()
  {
  }

  public function create(MailerDsn $dsn): TransportInterface
  {
    $this->doAdapt($dsn);
    return $this->transport;
  }

  public function doAdapt(Dsn $dsn)
  {
    try {
      $scheme = $dsn->getScheme();

      if (empty($scheme)) {
        throw new InvalidArgumentException('The mailer DSN must contain a scheme.');
      }

      $this->transport = Transport::fromDsn((string)$dsn);

      return $this->transport;
    } catch (Throwable $exception) {
      throw $exception;
    }
  }

  protected function getSupportedSchemes(): array
  {
    return ['smtp', 'null']; // Define a custom scheme that this factory handles
  }
}
