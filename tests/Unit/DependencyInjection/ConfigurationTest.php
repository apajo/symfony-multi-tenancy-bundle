<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\DependencyInjection;

use aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter;
use aPajo\MultiTenancyBundle\DependencyInjection\Configuration;
use aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
  /**
   * @param array<int, array<string, mixed>> $configs
   * @return array<string, mixed>
   */
  private function process(array $configs): array
  {
    return (new Processor())->processConfiguration(new Configuration(), $configs);
  }

  private function minimal(array $tenant = []): array
  {
    return [['tenant' => $tenant + ['class' => 'App\Entity\Tenant']]];
  }

  public function testTenantClassIsRequired(): void
  {
    $this->expectException(InvalidConfigurationException::class);

    $this->process([['tenant' => ['identifier' => 'slug']]]);
  }

  /**
   * Documents a validation gap rather than endorsing it.
   *
   * `tenant` is declared with addDefaultsIfNotSet(), which builds the node from
   * child defaults when the section is missing -- and that path does not
   * enforce isRequired() on `class`. So omitting `tenant` altogether passes
   * validation and yields a config with no `class` key at all, which only
   * surfaces later as an undefined-array-key error in TenantConfig.
   * Supplying `tenant` without `class` is correctly rejected (see above).
   */
  public function testOmittingTheTenantSectionEntirelySkipsTheRequiredClassCheck(): void
  {
    $config = $this->process([[]]);

    self::assertArrayNotHasKey('class', $config['tenant']);
    // The rest of the defaults still apply.
    self::assertSame('key', $config['tenant']['identifier']);
    self::assertSame('default', $config['tenant']['entity_manager']);
  }

  public function testDefaults(): void
  {
    $config = $this->process($this->minimal());

    self::assertSame([DatabaseAdapter::class], $config['adapters']);
    self::assertSame('App\Entity\Tenant', $config['tenant']['class']);
    self::assertSame('key', $config['tenant']['identifier']);
    self::assertSame('default', $config['tenant']['entity_manager']);
    self::assertSame([HostBasedResolver::class], $config['tenant']['resolvers']);
    self::assertSame('%kernel.project_dir%/config/migrations/default.yml', $config['migrations']['default']);
    self::assertSame('%kernel.project_dir%/config/migrations/tenant.yml', $config['migrations']['tenant']);
  }

  public function testTenantSectionIsFullyOverridable(): void
  {
    $config = $this->process($this->minimal([
      'identifier' => 'slug',
      'entity_manager' => 'tenant',
      'resolvers' => ['App\Resolver\HeaderResolver'],
    ]));

    self::assertSame('slug', $config['tenant']['identifier']);
    self::assertSame('tenant', $config['tenant']['entity_manager']);
    self::assertSame(['App\Resolver\HeaderResolver'], $config['tenant']['resolvers']);
  }

  public function testAdaptersReplaceRatherThanAppendToTheDefault(): void
  {
    $config = $this->process([[
      'adapters' => ['App\Adapter\CustomAdapter'],
      'tenant' => ['class' => 'App\Entity\Tenant'],
    ]]);

    self::assertSame(['App\Adapter\CustomAdapter'], $config['adapters']);
  }

  public function testMigrationPathsAreOverridable(): void
  {
    $config = $this->process([[
      'tenant' => ['class' => 'App\Entity\Tenant'],
      'migrations' => ['tenant' => 'config/custom/tenant.yml'],
    ]]);

    self::assertSame('config/custom/tenant.yml', $config['migrations']['tenant']);
    // The sibling key keeps its default rather than being wiped out.
    self::assertSame('%kernel.project_dir%/config/migrations/default.yml', $config['migrations']['default']);
  }

  public function testLaterConfigsOverrideEarlierOnes(): void
  {
    $config = $this->process([
      ['tenant' => ['class' => 'App\Entity\Tenant', 'identifier' => 'key']],
      ['tenant' => ['identifier' => 'slug']],
    ]);

    self::assertSame('slug', $config['tenant']['identifier']);
    self::assertSame('App\Entity\Tenant', $config['tenant']['class']);
  }
}
