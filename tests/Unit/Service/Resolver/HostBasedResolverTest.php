<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service\Resolver;

use aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver;
use aPajo\MultiTenancyBundle\Service\Resolver\TenantResolverInterface;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\User;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[CoversClass(HostBasedResolver::class)]
class HostBasedResolverTest extends TestCase
{
  public function testAlwaysSupports(): void
  {
    self::assertTrue($this->resolver()->supports());
    self::assertInstanceOf(TenantResolverInterface::class, $this->resolver());
  }

  public function testResolvesByRequestHost(): void
  {
    $tenant = (new Tenant())->setKey('acme')->setHost('acme.example.com');

    $repository = $this->createMock(EntityRepository::class);
    $repository->expects($this->once())
      ->method('findOneBy')
      ->with(['host' => 'acme.example.com'])
      ->willReturn($tenant);

    $resolver = $this->resolver(
      request: Request::create('https://acme.example.com/dashboard'),
      repository: $repository
    );

    self::assertSame($tenant, $resolver->resolve());
  }

  public function testReturnsNullWhenNoTenantMatchesTheHost(): void
  {
    $repository = $this->createMock(EntityRepository::class);
    $repository->method('findOneBy')->willReturn(null);

    $resolver = $this->resolver(
      request: Request::create('https://unknown.example.com/'),
      repository: $repository
    );

    self::assertNull($resolver->resolve());
  }

  public function testReturnsNullOutsideOfARequest(): void
  {
    // CLI commands resolve tenants explicitly, so there is no current request.
    $repository = $this->createMock(EntityRepository::class);
    $repository->expects($this->never())->method('findOneBy');

    self::assertNull($this->resolver(repository: $repository)->resolve());
  }

  public function testResolvesFromTheAuthenticatedUserBeforeFallingBackToHost(): void
  {
    $tokenStorage = new TokenStorage();
    $tokenStorage->setToken(new UsernamePasswordToken(
      new User('alice', null, ['ROLE_USER']),
      'main',
      ['ROLE_USER']
    ));

    $tenant = (new Tenant())->setKey('acme');

    $repository = $this->createMock(EntityRepository::class);
    $repository->method('findOneBy')->willReturn($tenant);

    $resolver = $this->resolver(
      request: Request::create('https://other.example.com/'),
      repository: $repository,
      tokenStorage: $tokenStorage
    );

    self::assertSame($tenant, $resolver->resolve());
  }

  private function resolver(
    ?Request $request = null,
    ?EntityRepository $repository = null,
    ?TokenStorage $tokenStorage = null,
  ): HostBasedResolver {
    $stack = new RequestStack();

    if ($request instanceof Request) {
      $stack->push($request);
    }

    $config = $this->createMock(TenantConfig::class);
    $config->method('getTenantIdentifierColumn')->willReturn('key');
    $config->method('getRepository')->willReturn($repository ?? $this->createMock(EntityRepository::class));

    return new HostBasedResolver($stack, $tokenStorage ?? new TokenStorage(), $config);
  }
}
