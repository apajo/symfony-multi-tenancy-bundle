<?php

namespace aPajo\MultiTenancyBundle\Tests\Entity\Default;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tenant registry entry, mapped on the `default` entity manager.
 *
 * Property roles, all resolved through PropertyAccess at runtime:
 *   - $key      the configured `tenant.identifier`
 *   - $host     matched by HostBasedResolver against Request::getHost()
 *   - $database DSN consumed by DatabaseAdapter (its `$property` is 'database')
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenants')]
class Tenant implements TenantInterface
{
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'AUTO')]
  #[ORM\Column(type: 'integer')]
  private ?int $id = null;

  // `key` is a reserved word in MySQL; the backticks tell Doctrine to quote it.
  #[ORM\Column(name: '`key`', type: 'string', length: 50, unique: true)]
  private ?string $key = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $host = null;

  // `database` is reserved in MySQL too -- see the note on $key above.
  #[ORM\Column(name: '`database`', type: 'string', length: 255, nullable: true)]
  private ?string $database = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $mailer = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getKey(): ?string
  {
    return $this->key;
  }

  public function setKey(?string $key): self
  {
    $this->key = $key;

    return $this;
  }

  public function getHost(): ?string
  {
    return $this->host;
  }

  public function setHost(?string $host): self
  {
    $this->host = $host;

    return $this;
  }

  public function getDatabase(): ?string
  {
    return $this->database;
  }

  public function setDatabase(?string $database): self
  {
    $this->database = $database;

    return $this;
  }

  public function getMailer(): ?string
  {
    return $this->mailer;
  }

  public function setMailer(?string $mailer): self
  {
    $this->mailer = $mailer;

    return $this;
  }
}
