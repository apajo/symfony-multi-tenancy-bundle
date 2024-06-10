# Symfony Multi Tenancy Bundle

## Description

## Requirements

- [Symfony 6.3 / 7](https://www.symfony.com/)
- [PHP 8.1](https://www.php.com/)

## Installation

```bash
$ composer require apajo/symfony-multi-tenancy-bundle
```

## Configuration

```yaml
apajo_multi_tenancy:
  adapters: # Adapters dynamically change the system configuration for selected tenant
    - aPajo\MultiTenancyBundle\Adapter\Database\DatabaseAdapter
    - aPajo\MultiTenancyBundle\Adapter\Filesystem\FilesystemAdapter
    - aPajo\MultiTenancyBundle\Adapter\Mailer\MailerAdapter

  tenant:                                   # Tenant (entity) configuration
    class: Core\TenantBundle\Entity\Tenant  # Must implement TenantInterface
    identifier: key                         # Identifier column name (must be unique field)
    entity_manager: default                 # Tenant entity manager name
    resolvers:                              # Resolvers resolve the tenant based on the request
      - aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver

  migrations: # Migration configuration
    namespace: App\Migrations\Tenant
    path: '%kernel.project_dir%/migrations/tenant'
```

## Quick start

### Known issues

* Resetting to default/initial tenant does not work

### Thanks to

This bundle is inspired by the [RamyHakam / multi_tenancy_bundle](https://github.com/RamyHakam/multi_tenancy_bundle)
