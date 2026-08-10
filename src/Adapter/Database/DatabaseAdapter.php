<?php

namespace aPajo\MultiTenancyBundle\Adapter\Database;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterInterface;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterTrait;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\DBAL\Connection;

class DatabaseAdapter implements PropertyAdapterInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'database';

  public function doAdapt(Dsn $dsn)
  {
    $this->reconnect($this->getConnectionParams($dsn));
  }

  protected function captureDefault(): mixed
  {
    return $this->connection()->getParams();
  }

  protected function restoreDefault(mixed $default): mixed
  {
    $this->reconnect($default);

    return null;
  }

  private function connection(): Connection
  {
    return $this->doctrine->getConnection('tenant');
  }

  /**
   * Rebuild the tenant connection in place with a new set of parameters.
   */
  private function reconnect(array $params): void
  {
    $connection = $this->connection();

    // Close the existing connection if it is open
    if ($connection->isConnected()) {
      $connection->close();
    }

    $connection->__construct(
      $params,
      $connection->getDriver(),
      $connection->getConfiguration(),
      $connection->getEventManager()
    );

    $connection->connect();
  }

  private function getConnectionParams(Dsn $dsn): array
  {
    $params = $this->getDefault() ?? [];

    // The connection was configured from a URL, which would take precedence
    // over the tenant values below if anything re-parsed these parameters.
    unset($params['url']);

    // Keep everything else the connection was configured with (charset,
    // serverVersion, driverOptions, ...) and override only what the tenant
    // DSN actually carries.
    return array_merge($params, [
      'dbname' => trim((string) $dsn->getPath(), '/'),
      'user' => $dsn->getUser(),
      'password' => $dsn->getPassword(),
      'host' => $dsn->getHost(),
      'port' => $dsn->getPort(),
      'driver' => sprintf('pdo_%s', $dsn->getScheme())
    ]);
  }

  public function __construct(
    private EventDispatcherInterface $dispatcher,
    private TenantConfig             $tenantConfig,
    private Registry                 $doctrine,
  )
  {
  }
}
