# Resolvers

Resolvers are responsible for resolving current tenant.

The TenantManager iterates over all resolvers until a resolver returns a non-null value.

```php
class TenantManager
{
  // ...
  
  public function resolve(): ?TenantInterface
  {
    foreach ($this->resolverRegistry->getResolvers() as $resolver) {
      if (!$resolver->supports()) {
        continue;
      }

      $tenant = $resolver->resolve();

      if ($tenant) {
        return $tenant;
      }
    }

    return null;
  }
  
  // ...
}
```


## Built-in resolvers

### HostBasedResolver

Resolves current tenant based on the request host value.
