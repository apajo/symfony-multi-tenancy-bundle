<?php

namespace aPajo\MultiTenancyBundle\Tests\Entity\Default;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface
{
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'AUTO')]
  #[ORM\Column(type: 'integer')]
  private ?int $id = null;

  #[ORM\ManyToOne(targetEntity: Tenant::class)]
  #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', onDelete: 'RESTRICT', nullable: true)]
  private ?TenantInterface $tenant = null;

  private string $username;
  private ?string $password;
  private bool $enabled;
  private array $roles;


  public function __construct(?string $username, ?string $password, array $roles = [], bool $enabled = true)
  {
    $this->username = $username;
    $this->password = $password;
    $this->enabled = $enabled;
    $this->roles = $roles;
  }

  public function getRoles(): array
  {
    return ['ROLE_USER'];
  }

  public function eraseCredentials(): void
  {
    // TODO: Implement eraseCredentials() method.
  }

  public function getUserIdentifier(): string
  {
    return $this->id;
  }

  public function getTenant(): ?TenantInterface
  {
    return $this->tenant;
  }

  public function setTenant(?TenantInterface $tenant = null): void
  {
    $this->tenant = $tenant;
  }
}
