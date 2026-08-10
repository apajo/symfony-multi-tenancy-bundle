# Adapters

Adapters are responsible for dynamic configuration changes based on tenant table values at runtime.

On `TenantSelectEvent` event dispatch, the system will iterate through all registered adapters and apply the changes.

When the event carries no tenant — `EnvironmentProvider::reset()`, `select(null)`, a request that
resolves to no tenant, or the end of a `for()`/`forAll()` iteration — every adapter reverts to the
configuration it was constructed with instead.

```php
#[AsEventListener(event: TenantSelectEvent::class, method: 'onTenantSelectEvent')]
class EnvironmentProvider
{
  // ...
  
  public function onTenantSelectEvent(TenantSelectEvent $event)
  {
    $tenant = $event->getTenant();

    $this->adapterRegistry->getAdapters()->map(function (AdapterInterface $adapter) use ($tenant) {
      // No tenant selected: every adapter goes back to its default configuration.
      if (!$tenant instanceof TenantInterface) {
        return $adapter->reset();
      }

      return $adapter->adapt($tenant);
    });
  }
}

```

## Writing your own adapter

Extend `PropertyAdapterTrait` and it takes care of reading the DSN off the tenant, of reverting when
a tenant leaves that property empty, and of `reset()`. You supply three methods:

```php
class MyAdapter implements PropertyAdapterInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'my_service'; // tenant property holding the DSN

  /** Apply a tenant DSN. */
  public function doAdapt(Dsn $dsn)
  {
    // ...
  }

  /** Snapshot of the configuration currently in place. */
  protected function captureDefault(): mixed
  {
    // ...
  }

  /** Put a snapshot taken by captureDefault() back in place. */
  protected function restoreDefault(mixed $default): mixed
  {
    // ...
  }
}
```

The snapshot is taken lazily, right before the first `adapt()` replaces anything, so it is always
the configuration the container wired up. `reset()` before any `adapt()` is a no-op.

## Built-in adapters

### DatabaseAdapter

Changes system database connection configuration DSN (Data Source Name) value

Changes the `doctrine.dbal.connections.tenant.url` value at runtime:

```yaml
doctrine:
  dbal:
    connections:
      tenant:
        url: '%database_url%'
```

__NB__ The connection name (in this case `tenant`) is configurable!

### FilesystemAdapter

Switches current Filesystem provider to a new Gaufrette FTP filesystem provider.

When using Sonata Media Bundle, this adapter will switch the current filesystem provider to a new Gaufrette FTP
filesystem provider.
Set the FilesystemAdapter as the provider for the Sonata Media Bundle in the configuration file.

```yaml
sonata_media:
  providers:
    file:
      filesystem: aPajo\MultiTenancyBundle\Adapter\Filesystem\FilesystemAdapter

  filesystem:
    ftp:
      passive: true
      create: true
      mode: 2

```

> __NB!__ This adapter supports only FTP filesystems at the moment. Feel free
> to [contribute](https://github.com/apajo/symfony-multi-tenancy-bundle/pulls)

### MailerAdapter

Updates symfony/mailer DSN value based on the tenant information.

Changes the `framework.mailer.dsn` value at runtime:

```yaml
framework:
  mailer:
    dsn: '%env(MAILER_DSN)%'
```

The adapter is registered as a `mailer.transport_factory` and hands the Mailer *itself* rather than a
concrete transport, delegating `send()` to whichever transport is current. That is what lets a tenant
switch — or a reset back to the `framework.mailer.dsn` transport — reach a Mailer that was already
built.
