<?php

namespace aPajo\MultiTenancyBundle\Tests\Entity\Tenant;

use Doctrine\ORM\Mapping as ORM;

/**
 * Per-tenant payload, mapped on the `tenant` entity manager.
 *
 * Exists so the database suite can prove a tenant switch actually moved reads
 * and writes to a different schema, rather than merely mutating connection
 * parameters in memory.
 */
#[ORM\Entity]
#[ORM\Table(name: 'items')]
class Item
{
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'AUTO')]
  #[ORM\Column(type: 'integer')]
  private ?int $id = null;

  #[ORM\Column(type: 'string', length: 50, nullable: true)]
  private ?string $name = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(?string $name): self
  {
    $this->name = $name;

    return $this;
  }
}
