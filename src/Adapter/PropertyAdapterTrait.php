<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait PropertyAdapterTrait
{
  /**
   * Configuration the adapter was constructed with, captured right before the
   * first adapt() replaces it.
   */
  private mixed $default = null;

  private bool $defaultCaptured = false;

  /**
   * Take a snapshot of the configuration currently in place.
   */
  abstract protected function captureDefault(): mixed;

  /**
   * Put a snapshot taken by captureDefault() back in place.
   */
  abstract protected function restoreDefault(mixed $default): mixed;

  public function setProperty(string $property): void
  {
    $this->property = $property;
  }

  public function adapt(TenantInterface $tenant)
  {
    $this->rememberDefault();

    $accessor = new PropertyAccessor();
    $dsn = $accessor->getValue($tenant, $this->property);

    if (!$dsn) {
      // The tenant carries no value for this adapter: fall back to the default
      // instead of leaving the previous tenant's configuration in place.
      return $this->reset();
    }

    return $this->doAdapt(Dsn::fromString($dsn));
  }

  public function reset()
  {
    if (!$this->defaultCaptured) {
      // Never adapted, so the default configuration is still in place.
      return null;
    }

    return $this->restoreDefault($this->default);
  }

  /**
   * Snapshot of the configuration the adapter was constructed with, or null
   * while nothing has been adapted yet.
   */
  protected function getDefault(): mixed
  {
    return $this->default;
  }

  private function rememberDefault(): void
  {
    if ($this->defaultCaptured) {
      return;
    }

    $this->default = $this->captureDefault();
    $this->defaultCaptured = true;
  }
}
