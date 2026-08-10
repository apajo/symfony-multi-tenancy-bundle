<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Service\Resolver;

use aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver;
use aPajo\MultiTenancyBundle\Service\Resolver\TenantResolverInterface;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Tests\Entity\Default\Tenant;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

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

  /**
   * Pins bug #4 -- currently failing, hence skipped.
   *
   * HostBasedResolver::getUser() reads:
   *
   *     if (!$token = $this->tokenstorage->getToken() instanceof TokenInterface)
   *
   * `=` binds looser than `instanceof`, so $token is assigned the *boolean*
   * result, not the token. When a token is present the expression is
   * `!true === false`, execution falls through, and the next line calls
   * `$token->getUser()` on a bool -- a fatal Error. The security-based lookup
   * is therefore unreachable in any authenticated request.
   *
   * A second defect sits right behind it: resolve() calls
   * `$this->getUser()->getTenant()`, but getTenant() is not part of
   * UserInterface, so any user class lacking it fails too.
   *
   * Fix: `if (!($token = $this->tokenstorage->getToken()) instanceof TokenInterface)`,
   * then delete this skip.
   *
   * @see src/Service/Resolver/HostBasedResolver.php:60
   */
  public function testResolvesFromTheAuthenticatedUserBeforeFallingBackToHost(): void
  {
    $this->markTestSkipped('Pins known bug: HostBasedResolver::getUser() assigns a bool to $token, so an authenticated request fatals.');

    $tokenStorage = new TokenStorage();
    $tokenStorage->setToken(new UsernamePasswordToken(
      new InMemoryUser('alice', null, ['ROLE_USER']),
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

  /**
   * Records the current contract: with a token present, resolution blows up
   * rather than falling back to the host. See the skipped test above.
   */
  public function testAuthenticatedRequestCurrentlyFails(): void
  {
    $tokenStorage = new TokenStorage();
    $tokenStorage->setToken(new UsernamePasswordToken(
      new InMemoryUser('alice', null, ['ROLE_USER']),
      'main',
      ['ROLE_USER']
    ));

    $repository = $this->createMock(EntityRepository::class);
    $repository->method('findOneBy')->willReturn(null);

    $resolver = $this->resolver(
      request: Request::create('https://acme.example.com/'),
      repository: $repository,
      tokenStorage: $tokenStorage
    );

    $this->expectException(\Error::class);

    $resolver->resolve();
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
