# Symfony Multi Tenancy Bundle
  
## Description

There are many packages that provide multi tenancy in Symfony.
All of them provide only different database configuration per tenant.

This package's goal is to provide a way to manage any kind of configuration in your system.

This bundle aims to provide multi tenancy on a higher level. It provides a way to dynamically
change the system configuration based on the tenant (database, media provider, mailer etc).

It also bundles a way to manage migrations for each tenant.

The package's development is in early stages, any feedback is welcome.

## Requirements

- [Symfony 6.4 / 7.1](https://www.symfony.com/)
- [Doctrine Bundle 2.12](https://github.com/doctrine/DoctrineBundle.git)
- [Doctrine Migrations Bundle 3.3](https://github.com/doctrine/migrations.git)
- [Symfony Security Bundley](https://symfony.com/components/Security%20Bundle)
- [PHP 8.2](https://www.php.com/)

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```bash
composer require apajo/symfony-multi-tenancy-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
composer require apajo/symfony-multi-tenancy-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    aPajo\MultiTenancyBundle\APajoMultiTenancyBundle::class => ['all' => true],
];
```

## Configuration

To change your 

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

## apajo_multi_tenancy.yml

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
      
  migrations: # Tenant Migration configuration
    default: '%kernel.project_dir%/config/migrations/default.yml'
    tenant: '%kernel.project_dir%/config/migrations/tenant.yml'

```

### Doctrine migrations configuration

Recommended path for the configuration files is `config/migrations/`.


#### default.yml

```yaml
migrations_paths:
  'App\Migrations\Default': 'migrations/default'
```

#### tenant.yml

```yaml
migrations_paths:
  'App\Migrations\Tenant': 'migrations/tenant'
```

#### doctrine_migrations.yml

```yaml
doctrine_migrations:
  migrations_paths:
    'App\Migrations\Default': '%kernel.project_dir%/migrations/default'
    'App\Migrations\Tenant': '%kernel.project_dir%/migrations/tenant'
```

## Adapters

Adapters are responsible for dynamic configuration changes based on tenant table values at runtime.

For more on (built-in) adapters see [Adapters directory](./src/Adapter/README.md)

## Resolvers

Resolvers are responsible for resolving current tenant.

For more on (built-in) resolvers see [Resolvers directory](./src/Service/Resolver/README.md)

## Database migrations

This bundle adds just 2 new commands to your project:

```shell
# Create new migrations/diffs (for default and tenant connections)

php bin/console tenants:migrations:diff
```

```shell
# Apply migrations to the tenants (or a single tenant) and the default connection

php bin/console tenants:migrations:migrate [tenant_id]
```


__NB!__ All other migration commands are as-is by [DoctrineMigrationsBundle](https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html) 

## Examples

### Switch/select tenant

```php
use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Entity\TenantInterface;
use aPajo\MultiTenancyBundle\Event\TenantSelectEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface

class Tenant implements TenantInterface {
    // ...
}

class MyTenantSelectService {
  public function __construct (
    private EnvironmentProvider $environmentProvider,
    private EventDispatcherInterface $dispatcher,
  ) {
  }
  
  
  /**
   * Use the EnvironmentProvider to select a different tenant
   */
  public function select () {
    $tenant = new Tenant();
    
    $environmentProvider->select($tenant);
    // Now the system is configured based on the tenant
  }

  /**
   * You can also dispatch an event to select a new tenant
   */
  public function alternativeSelect () {
      $tenant = new Tenant();
      
      $event = new TenantSelectEvent($tenant);
      $this->dispatcher->dispatch($event);
  }
}
```

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
## Development

### Testing

```shell
./vendor/bin/simple-phpunit
```


## Issues

Feel free to report an issue [under GitHub Issues](https://github.com/apajo/symfony-multi-tenancy-bundle/issues)

### Known Issues

* Resetting to default/initial tenant does not work
* Symfony profiler currently shows only default entity managers migrations

## Contributing

Feel free to [contribute](https://github.com/apajo/symfony-multi-tenancy-bundle/pulls)

### Versioning

Bundles must be versioned following the [Semantic Versioning Specification](https://semver.org/).

### Thanks to

This bundle is inspired by the [RamyHakam / multi_tenancy_bundle](https://github.com/RamyHakam/multi_tenancy_bundle)
