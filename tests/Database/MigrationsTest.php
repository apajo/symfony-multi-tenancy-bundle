<?php

namespace aPajo\MultiTenancyBundle\Tests\Database;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Migration\Command\DiffCommand;
use aPajo\MultiTenancyBundle\Migration\Command\MigrateCommand;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Covers the tenant migration surface.
 *
 * The diff command shells out to `bin/console doctrine:migrations:diff`, so
 * these tests need both connections live and a writable migrations/ directory
 * (already gitignored). Generated files are cleaned up afterwards.
 */
#[CoversClass(DiffCommand::class)]
#[CoversClass(MigrateCommand::class)]
#[CoversClass(MigrationManager::class)]
class MigrationsTest extends AbstractDatabaseTestCase
{
  private ContainerInterface $container;

  private Filesystem $filesystem;

  /** @var list<string> */
  private array $generated = [];

  protected function setUp(): void
  {
    parent::setUp();

    $this->container = $this->boot();
    $this->filesystem = new Filesystem();
  }

  protected function tearDown(): void
  {
    // Must run: migrations are really applied now, so a leftover file from an
    // earlier run replays CREATE TABLE against a schema that already has it.
    foreach ($this->generated as $file) {
      $this->filesystem->remove($file);
    }

    $this->generated = [];

    parent::tearDown();
  }

  private function projectDir(): string
  {
    return self::$kernel->getProjectDir();
  }

  /** @return list<string> */
  private function migrationFiles(string $namespace): array
  {
    $files = glob($this->projectDir() . '/migrations/' . $namespace . '/Version*.php');

    return $files === false ? [] : array_values($files);
  }

  public function testMigrationDirectoriesAreRegisteredWithDoctrine(): void
  {
    // Contributed by MigrationsPathsCompilerPass.
    $configuration = $this->container->get('doctrine.migrations.dependency_factory')->getConfiguration();

    $directories = $configuration->getMigrationDirectories();

    self::assertArrayHasKey('App\Migrations\Default', $directories);
    self::assertArrayHasKey('App\Migrations\Tenant', $directories);
  }

  public function testMigrationConfigIsInjectedIntoTheManager(): void
  {
    $manager = $this->container->get(MigrationManager::class);

    $config = (new \ReflectionProperty(MigrationManager::class, 'config'))->getValue($manager);

    self::assertArrayHasKey('default', $config);
    self::assertArrayHasKey('tenant', $config);
    self::assertStringEndsWith('config/migrations/default.yml', $config['default']);
    self::assertStringEndsWith('config/migrations/tenant.yml', $config['tenant']);
  }

  public function testDefaultMigrationDoesNotRequestANamedManagerFromItsBoundFactory(): void
  {
    $generated = $this->generateMigrations();
    self::assertNotEmpty($generated['default'], 'the default manager needs a migration to apply');

    $this->container->get('doctrine.migrations.dependency_factory')->freeze();

    $application = new Application(self::$kernel);
    $application->setAutoExit(false);
    $application->add($this->container->get(MigrateCommand::class));

    $input = new ArrayInput([
      'command' => 'tenants:migrations:migrate',
      'tenant_id' => 'no-such-tenant',
    ]);
    $input->setInteractive(false);

    self::assertSame(Command::SUCCESS, $application->run($input, new BufferedOutput()));

    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');
    self::assertGreaterThan(
      0,
      (int) $doctrine->getConnection('default')->fetchOne('SELECT COUNT(*) FROM doctrine_migration_versions'),
      'the default migration must run through its factory-bound entity manager'
    );
  }

  public function testMigrationsWorkForDefaultAndTenantEntityManagersInOneCommand(): void
  {
    $generated = $this->generateMigrations();
    self::assertNotEmpty($generated['default']);
    self::assertNotEmpty($generated['tenant']);

    $this->dropAllTablesIn(self::ALTERNATE_TENANT_SCHEMA);

    // The wrapper always migrates the default manager first. No tenant is
    // selected here, so this call isolates the default manager's factory.
    $application = new Application(self::$kernel);
    $application->setAutoExit(false);
    $application->add($this->container->get(MigrateCommand::class));

    $input = new ArrayInput([
      'command' => 'tenants:migrations:migrate',
      'tenant_id' => 'no-such-tenant',
    ]);
    $input->setInteractive(false);
    self::assertSame(Command::SUCCESS, $application->run($input, new BufferedOutput()));

    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');
    self::assertGreaterThan(
      0,
      (int) $doctrine->getConnection('default')->fetchOne('SELECT COUNT(*) FROM doctrine_migration_versions'),
      'the default entity manager should record its migration'
    );

    // Seed a tenant only after the default migration so the migration-created
    // registry table does not collide with SchemaTool's table creation.
    $this->seedTenant();

    // A full wrapper run first sees the already migrated default manager, then
    // switches the tenant connection and runs the tenant migration.
    $input = new ArrayInput(['command' => 'tenants:migrations:migrate']);
    $input->setInteractive(false);
    self::assertSame(Command::SUCCESS, $application->run($input, new BufferedOutput()));

    self::assertGreaterThan(
      0,
      $this->migrationVersionCountIn(self::ALTERNATE_TENANT_SCHEMA),
      'the tenant entity manager should record its migration in the tenant schema'
    );
  }

  public function testDiffCommandGeneratesMigrationsForBothEntityManagers(): void
  {
    $generated = $this->generateMigrations();

    foreach (['default', 'tenant'] as $namespace) {
      self::assertNotEmpty(
        $generated[$namespace],
        sprintf('expected a migration to be generated for the %s namespace', $namespace)
      );
    }
  }

  /**
   * Run `tenants:migrations:diff` and return the files it produced, keyed by
   * namespace. Every file is registered for removal in tearDown().
   *
   * The migrate tests need a migration to apply and must not depend on one left
   * behind by an earlier run: migrations are really applied now, so a stale file
   * would replay its CREATE TABLE against a schema that already has it.
   *
   * @return array{default: list<string>, tenant: list<string>}
   */
  private function generateMigrations(): array
  {
    $before = [
      'default' => $this->migrationFiles('default'),
      'tenant' => $this->migrationFiles('tenant'),
    ];

    // Start from empty schemas so there is always something to diff.
    $this->dropAllTables('default');
    $this->dropAllTables('tenant');

    $process = new Process(
      ['php', 'bin/console', 'tenants:migrations:diff'],
      $this->projectDir()
    );
    $process->setTimeout(120);
    $process->run();

    self::assertTrue(
      $process->isSuccessful(),
      'tenants:migrations:diff failed: ' . $process->getErrorOutput() . $process->getOutput()
    );

    $generated = [];

    foreach (['default', 'tenant'] as $namespace) {
      $new = array_values(array_diff($this->migrationFiles($namespace), $before[$namespace]));

      $generated[$namespace] = $new;
      $this->generated = array_merge($this->generated, $new);
    }

    return $generated;
  }

  public function testMigrationManagerRunsInsideTheTenantEnvironment(): void
  {
    $this->generateMigrations();
    $this->dropAllTablesIn(self::ALTERNATE_TENANT_SCHEMA);

    $manager = $this->container->get(MigrationManager::class);

    $tenant = (new Tenant())
      ->setKey('acme')
      ->setDatabase(self::tenantDsn(self::ALTERNATE_TENANT_SCHEMA));

    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');

    $manager->migrate($tenant);

    // for() must leave tenant context again once the callback returns.
    self::assertSame(
      self::TENANT_SCHEMA,
      $doctrine->getConnection('tenant')->getDatabase(),
      'the tenant connection should be reset after migrate()'
    );
  }

  public function testMigrationManagerUsesFreshDependenciesWhenDoctrineFactoryIsFrozen(): void
  {
    $this->generateMigrations();
    $this->dropAllTablesIn(self::ALTERNATE_TENANT_SCHEMA);

    $manager = $this->container->get(MigrationManager::class);
    $tenantFactory = $this->container->get('doctrine.migrations.dependency_factory');
    $tenantFactory->freeze();
    self::assertTrue($tenantFactory->isFrozen());

    $alternateTenant = (new Tenant())
      ->setKey('alpha')
      ->setDatabase(self::tenantDsn(self::ALTERNATE_TENANT_SCHEMA));
    $defaultTenant = (new Tenant())
      ->setKey('beta')
      ->setDatabase(self::tenantDsn(self::TENANT_SCHEMA));

    self::assertTrue($manager->migrate($alternateTenant));
    self::assertTrue(
      $tenantFactory->isFrozen(),
      'tenant migrations must not try to reconfigure Doctrine\'s shared frozen factory'
    );
    self::assertTrue($manager->migrate($defaultTenant));
    self::assertTrue(
      $tenantFactory->isFrozen(),
      'migrating another tenant must leave the shared factory untouched'
    );
  }

  public function testMigrateByKeyLooksTheTenantUpByItsIdentifier(): void
  {
    $this->generateMigrations();
    $this->dropAllTablesIn(self::ALTERNATE_TENANT_SCHEMA);
    $this->seedTenant();

    $manager = $this->container->get(MigrationManager::class);

    $manager->migrateByKey('acme');



    self::assertSame(
      'key',
      $this->container->get(TenantConfig::class)->getTenantIdentifierColumn()
    );
  }

  public function testMigrateHonoursTheRequestedVersion(): void
  {
    // Registered migrations must exist, or the command would fail for the
    // wrong reason and the assertion below would prove nothing.
    $this->generateMigrations();
    $this->dropAllTablesIn(self::ALTERNATE_TENANT_SCHEMA);

    /** @var MigrationManager $manager */
    $manager = $this->container->get(MigrationManager::class);

    $tenant = (new Tenant())
      ->setKey('acme')
      ->setDatabase(self::tenantDsn(self::ALTERNATE_TENANT_SCHEMA));

    // An unknown version must fail loudly rather than quietly migrating to
    // 'latest'. That the argument reaches the command at all is what this pins:
    // it used to be dropped, because the closure building the ArrayInput never
    // imported $version.
    //
    // Not '00000000000000' -- Doctrine reads that as "revert everything", which
    // is a legitimate no-op here and would pass without the version mattering.
    $this->expectException(\Throwable::class);

    $manager->migrate($tenant, '99999999999999');
  }

  private function seedTenant(): void
  {
    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');
    $em = $doctrine->getManager('default');

    $this->resetSchema($em);

    $em->persist(
      (new Tenant())
        ->setKey('acme')
        ->setHost('acme.example.com')
        ->setDatabase(self::tenantDsn(self::ALTERNATE_TENANT_SCHEMA))
    );
    $em->flush();
    $em->clear();
  }

  /**
   * Empty an arbitrary schema, addressed by name rather than through a
   * configured connection.
   *
   * Migrations are really applied now (they used to run with --dry-run), so the
   * schema a migration targets has to start empty. The alternate tenant schema
   * is shared with DatabaseAdapterTest, which leaves its own tables behind.
   */
  private function dropAllTablesIn(string $schema): void
  {
    $pdo = $this->schemaConnection($schema);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
      // Identifiers come from SHOW TABLES on a schema we just created.
      $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
  }

  private function migrationVersionCountIn(string $schema): int
  {
    return (int) $this->schemaConnection($schema)->query(
      'SELECT COUNT(*) FROM doctrine_migration_versions'
    )->fetchColumn();
  }

  private function schemaConnection(string $schema): \PDO
  {
    $dsn = Dsn::fromString(self::tenantDsn($schema));

    return new \PDO(
      sprintf('mysql:host=%s;port=%d;dbname=%s', $dsn->getHost(), $dsn->getPort(3306), $schema),
      $dsn->getUser(),
      $dsn->getPassword(),
      [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
  }

  private function dropAllTables(string $manager): void
  {
    /** @var Registry $doctrine */
    $doctrine = $this->container->get('doctrine');
    $connection = $doctrine->getConnection($manager);

    $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

    foreach ($connection->createSchemaManager()->listTableNames() as $table) {
      $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`', $table));
    }

    $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
  }
}
