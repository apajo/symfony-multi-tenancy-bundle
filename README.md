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

```bash
apajo_multi_tenancy:
  adapters: # Adapters dynamically change the system configuration for selected tenant
    - aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter
    - aPajo\MultiTenancyBundle\Adapter\Filesystem\FilesystemAdapter
    - aPajo\MultiTenancyBundle\Adapter\Mailer\MailerAdapter
  
  tenant:                                   # Tenant (entity) configuration
    class: App\Entity\Tenant  # Must implement TenantInterface
    identifier: key                         # Identifier column name (must be unique field)
    entity_manager: default                 # Tenant entity manager name
    resolvers:                              # Resolvers resolve the tenant based on the request
      - aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver 
      
  migrations: # Migration configuration
    namespace: App\Migrations\Tenant
    entity_manager: tenant
```

## Adapters

For more on (built-in) adapters see [Adapters directory](./src/Adapter/README.md)


## Known Issues

Please refer to the "Issues" section of this repository for known issues and their status.

### Thanks to

This bundle is inspired by the [RamyHakam / multi_tenancy_bundle](https://github.com/RamyHakam/multi_tenancy_bundle)
