<?php

namespace aPajo\MultiTenancyBundle\Entity;

/**
 * Contract every tenant entity must satisfy.
 *
 * The concrete class is supplied through `apajo_multi_tenancy.tenant.class`.
 * Beyond the identity accessor below, the entity is expected to expose the
 * property named by `apajo_multi_tenancy.tenant.identifier` (default `key`)
 * plus one property per configured adapter (e.g. `database` for the
 * DatabaseAdapter), each read through the PropertyAccess component.
 */
interface TenantInterface
{
  public function getId(): ?int;
}
