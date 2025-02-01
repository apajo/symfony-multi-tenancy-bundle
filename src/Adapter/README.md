# Adapters

Adapters are responsible for dynamic configuration changes based on tenant table values at runtime.

On `TenantSelectEvent` event dispatch, the system will iterate through all registered adapters and apply the changes.

```php
#[AsEventListener(event: TenantSelectEvent::class, method: 'onTenantSelectEvent')]
class EnvironmentProvider
{
  // ...
  
  public function onTenantSelectEvent(TenantSelectEvent $event)
  {
    $tenant = $event->getTenant();

    // TODO: implement null-tenant handling
    if (!$tenant) {
      return;
    }

    $this->adapterRegistry->getAdapters()->map(function (AdapterInterface $adapter) use ($tenant) {
      $adapter->adapt($tenant);
    });
  }
}

```

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
