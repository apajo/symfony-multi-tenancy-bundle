# Symfony Multi Tenancy Bundle

## Description

This bundles aim is to ease and automate multi tenancy in symfony.
It provides a way to dynamically change the system configuration based on the tenant (database, media, smtp etc).

## Requirements

- [Symfony 6.3 / 7](https://www.symfony.com/)
- [PHP 8.1](https://www.php.com/)

## Installation

```bash
composer require apajo/symfony-multi-tenancy-bundle
```

## Configuration

The `aPajo\MultiTenancyBundle\Adapter\Mailer\MailerAdapter` requires the following configuration:

- `tenant`: This section is for configuring the Tenant entity.
  - `class`: The class of your Tenant entity. It must implement the `TenantInterface`. For example, `App\Entity\Tenant`.
  - `identifier`: The name of the unique identifier column in your Tenant entity. For example, `key`.
  - `entity_manager`: The name of the entity manager for your Tenant entity. The default is `default`.
  - `resolvers`: These are the services that resolve the tenant based on the request. For example, `aPajo\MultiTenancyBundle\Service\Resolver\HostBasedResolver`.

- `migrations`: This section is for configuring migrations.
  - `namespace`: The namespace for your migrations. For example, `App\Migrations\Tenant`.
  - `path`: The path to your migrations. For example, `'%kernel.project_dir%/migrations/tenant'`.

## Adapters

For more on (built-in) adapters see [Adapters directory](./src/Adapter/README.md)


## Known Issues

Please refer to the "Issues" section of this repository for known issues and their status.

### Thanks to

This bundle is inspired by the [RamyHakam / multi_tenancy_bundle](https://github.com/RamyHakam/multi_tenancy_bundle)
