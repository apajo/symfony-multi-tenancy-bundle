<?php

namespace aPajo\MultiTenancyBundle\Adapter\Database;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterInterface;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterTrait;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DatabaseAdapter implements PropertyAdapterInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'database';

  public function doAdapt(Dsn $dsn): void
  {
    /** @var Connection $connection */
    $connection = $this->doctrine->getConnection('tenant');

    // Get new connection parameters
    $newParams = $this->getConnectionParams(Dsn::fromString($dsn));

    // Close the existing connection if it is open
    if ($connection->isConnected()) {
      $connection->close();
    }

    $connection->__construct(
      $newParams,
      $connection->getDriver(),
      $connection->getConfiguration(),
      $connection->getEventManager()
    );

    $connection->connect();
  }

  private function getConnectionParams(Dsn $dsn): array
  {
    return [
      'dbname' => trim((string) $dsn->getPath(), '/'),
      'user' => $dsn->getUser(),
      'password' => $dsn->getPassword(),
      'host' => $dsn->getHost(),
      'port' => $dsn->getPort(),
      'driver' => sprintf('pdo_%s', $dsn->getScheme())
    ];
  }

  public function __construct(
    private EventDispatcherInterface $dispatcher,
    private TenantConfig             $tenantConfig,
    private Registry                 $doctrine,
  )
  {
  }
}
