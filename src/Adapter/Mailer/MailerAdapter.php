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

class MailerAdapter extends AbstractTransportFactory implements PropertyAdapterInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'mailer';

  protected $transport;

  public function create(MailerDsn $dsn): TransportInterface
  {
    $this->doAdapt(new Dsn(
      $dsn->getScheme(),
      $dsn->getHost(),
      $dsn->getUser(),
      $dsn->getPassword(),
      $dsn->getPort()
    ));

    return $this->transport;
  }

  public function doAdapt(Dsn $dsn)
  {
    $scheme = $dsn->getScheme();
    if ($scheme === '' || $scheme === '0') {
      throw new InvalidArgumentException('The mailer DSN must contain a scheme.');
    }
    $this->transport = Transport::fromDsn((string)$dsn);
    return $this->transport;
  }

  protected function getSupportedSchemes(): array
  {
    return ['smtp', 'null']; // Define a custom scheme that this factory handles
  }
}
