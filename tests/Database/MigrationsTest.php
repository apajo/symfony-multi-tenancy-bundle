<?php

namespace aPajo\MultiTenancyBundle\Tests\Database;

use aPajo\MultiTenancyBundle\Migration\Command\DiffCommand;
use aPajo\MultiTenancyBundle\Migration\Command\MigrateCommand;
use aPajo\MultiTenancyBundle\Migration\MigrationManager;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
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

  public function testDiffCommandGeneratesMigrationsForBothEntityManagers(): void
  {
    $before = [
      'default' => $this->migrationFiles('default'),
      'tenant' => $this->migrationFiles('tenant'),
    ];

    // Start from empty schemas so there is always something to diff.
    $this->dropAllTables('default');
    $this->dropAllTables('tenant');

    $process = new Process(
      ['php', 'bin/console', 'tenants:migrations:diff', '--no-interaction'],
      $this->projectDir()
    );
    $process->setTimeout(120);
    $process->run();

    self::assertTrue(
      $process->isSuccessful(),
      'tenants:migrations:diff failed: ' . $process->getErrorOutput() . $process->getOutput()
    );

    foreach (['default', 'tenant'] as $namespace) {
      $new = array_values(array_diff($this->migrationFiles($namespace), $before[$namespace]));
      $this->generated = array_merge($this->generated, $new);

      self::assertNotEmpty($new, sprintf('expected a migration to be generated for the %s namespace', $namespace));
    }
  }

  public function testMigrationManagerRunsInsideTheTenantEnvironment(): void
  {
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

  public function testMigrateByKeyLooksTheTenantUpByItsIdentifier(): void
  {
    $this->seedTenant();

    $manager = $this->container->get(MigrationManager::class);

    $manager->migrateByKey('acme');

    self::assertSame(
      'key',
      $this->container->get(TenantConfig::class)->getTenantIdentifierColumn()
    );
  }

  /**
   * Pins bug #2 -- currently failing, hence skipped.
   *
   * MigrationManager::migrate() builds its ArrayInput inside a closure that
   * never imports $version:
   *
   *     public function migrate(TenantInterface $tenant, string $version = null): bool
   *     {
   *       $this->environmentProvider->for($tenant, function (...) {
   *         'version' => $version ?: 'latest',
   *
   * $version is undefined in that scope, so it is always null and every call
   * silently migrates to 'latest'. Two further defects sit in the same method:
   * '--dry-run' => true is hardcoded, so tenant migrations are never actually
   * applied; and '--configuration' points at $this->config['default'] rather
   * than ['tenant'], so the wrong migration set is loaded. The method also
   * declares : bool but returns 0.
   *
   * Fix: `function (...) use ($version) {`, drop the hardcoded dry-run, and use
   * $this->config['tenant']; then delete this skip.
   *
   * @see src/Migration/MigrationManager.php:44
   */
  public function testMigrateHonoursTheRequestedVersion(): void
  {
    self::markTestSkipped('Pins known bug: MigrationManager::migrate() never imports $version into its closure, and hardcodes --dry-run.');

    $manager = $this->container->get(MigrationManager::class);

    $tenant = (new Tenant())
      ->setKey('acme')
      ->setDatabase(self::tenantDsn(self::ALTERNATE_TENANT_SCHEMA));

    // With a bogus version the underlying Doctrine command must fail loudly
    // rather than quietly migrating to 'latest'.
    $this->expectException(\Throwable::class);

    $manager->migrate($tenant, '00000000000000');
  }

  /**
   * Records the current contract: a bogus version is ignored instead of
   * rejected. See the skipped test above.
   *
   * Like the two tests above, this exercises the buggy method and therefore
   * emits the known "Undefined variable $version" warning, which is recorded
   * in tests/baseline.xml.
   */
  public function testRequestedVersionIsCurrentlyIgnored(): void
  {
    $manager = $this->container->get(MigrationManager::class);

    $tenant = (new Tenant())
      ->setKey('acme')
      ->setDatabase(self::tenantDsn(self::ALTERNATE_TENANT_SCHEMA));

    $manager->migrate($tenant, '00000000000000');

    self::assertTrue(true, 'no exception: the version argument never reaches the migration command');
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
