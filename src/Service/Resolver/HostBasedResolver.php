<?php

namespace aPajo\MultiTenancyBundle\Service\Resolver;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;

class HostBasedResolver implements TenantResolverInterface
{
  public function __construct(
    private RequestStack $requestStack,
    private TokenStorage $tokenstorage,
    private TenantConfig $config,
  )
  {
  }

  public function resolve(): ?TenantInterface
  {
    if (
      $this->getUser(false) &&
      $tenant = $this->resolveByCrits([
        $this->config->getTenantIdentifierColumn() => $this->getUser()->getTenant()
      ])
    ) {
      return $tenant;
    }

    if (
      $this->getHost() &&
      $tenant = $this->resolveByCrits([
        'host' => $this->getHost()
      ])
    ) {
      return $tenant;
    }

    return null;
  }

  /**
   * Get user from token
   * @param bool $exception if true, throws LogicException when tokenstorage has not been set
   * @return UserInterface|null
   * @throws LogicException When SecurityBundle has not been set and $exception is true
   */
  protected function getUser($exception = true): ?UserInterface
  {
    if (!$this->tokenstorage && $exception) {
      throw new LogicException('The SecurityBundle is not registered in your application.');
    } elseif (!$this->tokenstorage && !$exception) {
      return null;
    }

    if (null === $token = $this->tokenstorage->getToken()) {
      return null;
    }

    if (!is_object($user = $token->getUser())) {
      // e.g. anonymous authentication
      return null;
    }

    return $user;
  }

  private function resolveByCrits(array $crits): ?TenantInterface
  {
    return $this->config->getRepository()->findOneBy($crits);
  }

  private function getHost(): ?string
  {
    $request = $this->requestStack->getCurrentRequest();

    if (!$request) {
      return null;
    }

    return $request->getHost();
  }

  public function supports(): bool
  {
    return true;
  }
}
