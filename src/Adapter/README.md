# Adapters (Symfony Multi Tenancy Bundle)

Adapters are responsible for dynamic configuration changes based on tenant table values at runtime.

## Built-in adapters

### DatabaseAdapter
Changes system database connection configuration (DSN) value

Changes the doctrine.dbal.connections.tenant.url value at runtime:
```yaml
doctrine:
  dbal:
    connections:
      tenant:
        url: '%database_url%'
```

__NB__ The conntection name (in this case `tenant`) is configurable!

### FilesystemAdapter
Switches current Filesystem provider to a new Gaufrette FTP filesystem provider.

When using Sonata Media Bundle, this adapter will switch the current filesystem provider to a new Gaufrette FTP filesystem provider.
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

### MailerAdapter
Updates symfony/mailer DSN value based on the tenant information.

Changes the framework.mailer.dsn value based at runtime:
```yaml
framework:
  mailer:
    dsn: '%env(MAILER_DSN)%'
```
