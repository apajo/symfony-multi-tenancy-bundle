# Symfony Multi Tenancy Bundle
  
## Description

This bundles aim is to ease and automate multi tenancy in symfony.
It provides a way to dynamically change the system configuration based on the tenant (database, media, smtp etc).

## Requirements

- [Symfony 6.3 / 7](https://www.symfony.com/)
- [Doctrine Bundle 2.12](https://github.com/doctrine/DoctrineBundle.git)
- [Doctrine Migrations Bundle 3.3](https://github.com/doctrine/migrations.git)
- [PHP 8.1](https://www.php.com/)

## Installation

```bash
composer require apajo/symfony-multi-tenancy-bundle
```

## Configuration

### doctrine.yml

You need 2 connections and entity_managers:

```yaml
doctrine:
  dbal:
    default_connection: default
    connections:
      default:
        url: '%env(DEFAULT_DATABASE_URL)%'
        driver: pdo_mysql
        charset: utf8
        server_version: '8'

      tenant:
        url: '%env(TENANT_DATABASE_URL)%'
        driver:   pdo_mysql
        charset:  utf8
        server_version: '8'

  orm:
    default_entity_manager: default
    auto_generate_proxy_classes: true
    
    entity_managers:
      default:
        connection: default
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware

      tenant:
        connection: tenant
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        mappings:
```

In this case thay are named `default` and `tenant` but you can name them as you wish.

> __NB!__ Third party packages may require the `default` connection to be present so you might want to keep the `default` name.

Connection and entity manager `default` are common for all the individual tenants.
Connection and entity manager `tenant` are specific for the tenant.

### doctrine_migrations.yml

```yaml
doctrine_migrations:
  migrations_paths:
    'App\Migrations\Default': '%kernel.project_dir%/migrations/default'
    'App\Migrations\Tenant': '%kernel.project_dir%/migrations/tenant'
```

## Configuration

```yaml
apajo_multi_tenancy:
  adapters: # Adapters dynamically change the system configuration for selected tenant
    - aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter
    - aPajo\MultiTenancyBundle\Adapter\Filesystem\FilesystemAdapter
    - aPajo\MultiTenancyBundle\Adapter\Mailer\MailerAdapter
  
  tenant:                                   # Tenant (entity) configuration
    class: App\Entity\Tenant                # Must implement TenantInterface
    identifier: key                         # Identifier column name (must be unique field)
    entity_manager: default                 # Tenant entity manager name
    resolvers:                              # Resolvers resolve the tenant based on the request
      - aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver 
      
  migrations: # Migration configuration
    namespace: App\Migrations\Tenant
    entity_manager: tenant
```

## Adapters

Adapters are responsible for dynamic configuration changes based on tenant table values at runtime.

For more on (built-in) adapters see [Adapters directory](./src/Adapter/README.md)

## Examples

### Iterate over all tenant environments

```php
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Entity\TenantInterface;

class MyTenantService {
  public function __construct (
    private EnvironmentProvider $environmentProvider,
  ) {
  
    $environmentProvider->forAll(function (TenantInterface $tenant) {
      // Each iteration will have tenant specific configuration/environment
    });
    
  }

}
```


## Issues

### Known Issues

* Resetting to default/initial tenant does not work

### Thanks to

This bundle is inspired by the [RamyHakam / multi_tenancy_bundle](https://github.com/RamyHakam/multi_tenancy_bundle)
