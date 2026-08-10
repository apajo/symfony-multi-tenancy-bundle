<?php

namespace aPajo\MultiTenancyBundle\Tests\Database;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Tests\AbstractKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Base class for tests that need a real database.
 *
 * There is no pdo_sqlite on this platform, so these run against MySQL. They use
 * dedicated `*_test` schemas which they create and tear down themselves, and
 * they never touch application data.
 *
 * An unreachable server is a hard error, not a skip: this tier exists to prove
 * the adapters really switch connections, and a run that silently reports green
 * without ever opening one proves nothing. Use the `unit` and `integration`
 * suites for a run that needs no infrastructure.
 */
abstract class AbstractDatabaseTestCase extends AbstractKernelTestCase
{
  /** Registry of tenants; mirrors DEFAULT_DATABASE_URL. */
  protected const DEFAULT_SCHEMA = 'mtb_default_test';

  /** Per-tenant schema the `tenant` connection points at initially. */
  protected const TENANT_SCHEMA = 'mtb_tenant_test';

  /** A second per-tenant schema, used to prove a switch actually happened. */
  protected const ALTERNATE_TENANT_SCHEMA = 'mtb_tenant_alpha_test';

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();

    $dsn = self::serverDsn();

    try {
      $pdo = self::connect($dsn);
    } catch (PDOException $e) {
      throw new RuntimeException(sprintf(
        'MySQL is not reachable at %s:%d (%s). Run "make up" to start the '
          . 'bundled test database, point DEFAULT_DATABASE_URL/TENANT_DATABASE_URL '
          . 'at another server via .env.test.local, or run "composer test:unit" '
          . 'and "composer test:integration", which need no infrastructure.',
        $dsn->getHost(),
        $dsn->getPort(3306),
        $e->getMessage()
      ), 0, $e);
    }

    foreach ([self::DEFAULT_SCHEMA, self::TENANT_SCHEMA, self::ALTERNATE_TENANT_SCHEMA] as $schema) {
      // Identifiers are class constants, never user input.
      $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4', $schema));
    }
  }

  /**
   * The tenant DSN pointing at a given schema, reusing the credentials the
   * `tenant` connection is configured with.
   */
  protected static function tenantDsn(string $schema): string
  {
    $dsn = Dsn::fromString(self::envDsn('TENANT_DATABASE_URL'));

    return (string) new Dsn(
      $dsn->getScheme(),
      $dsn->getHost(),
      $dsn->getUser(),
      $dsn->getPassword(),
      $dsn->getPort(3306),
      $schema
    );
  }

  /**
   * Rebuild the mapped tables for an entity manager.
   */
  protected function resetSchema(EntityManagerInterface $em): void
  {
    $metadata = $em->getMetadataFactory()->getAllMetadata();

    if ($metadata === []) {
      return;
    }

    $tool = new SchemaTool($em);
    $tool->dropSchema($metadata);
    $tool->createSchema($metadata);
  }

  protected static function envDsn(string $name): string
  {
    $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

    if (!is_string($value) || $value === '') {
      throw new RuntimeException(sprintf('%s is not set.', $name));
    }

    return $value;
  }

  /**
   * Server-level DSN (no schema), used to create the test databases.
   */
  private static function serverDsn(): Dsn
  {
    return Dsn::fromString(self::envDsn('DEFAULT_DATABASE_URL'));
  }

  private static function connect(Dsn $dsn): PDO
  {
    return new PDO(
      sprintf('mysql:host=%s;port=%d', $dsn->getHost(), $dsn->getPort(3306)),
      $dsn->getUser(),
      $dsn->getPassword(),
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
  }
}
