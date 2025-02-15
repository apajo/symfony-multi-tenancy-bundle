<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use SensitiveParameter;

final class Dsn implements \Stringable
{
  public function __construct(private readonly string $scheme, private readonly string $host, private readonly ?string $user = null, #[SensitiveParameter]
 private readonly ?string $password = null, private readonly ?int $port = null, private readonly ?string $path = null, private array $options = [])
  {
  }

  public static function fromString(#[SensitiveParameter] string $dsn): self
  {
    if (false === $params = parse_url($dsn)) {
      throw new InvalidArgumentException('The DSN is invalid.');
    }

    if (!isset($params['scheme'])) {
      throw new InvalidArgumentException('The DSN must contain a scheme.');
    }

    if (!isset($params['host'])) {
      throw new InvalidArgumentException('The DSN must contain a host (use "default" by default).');
    }

    $user = '' !== ($params['user'] ?? '') ? rawurldecode($params['user']) : null;
    $password = '' !== ($params['pass'] ?? '') ? rawurldecode($params['pass']) : null;
    $port = $params['port'] ?? null;
    $path = $params['path'] ?? null;
    $options = [];

    if (isset($params['query'])) {
      parse_str($params['query'], $output);
      $options = $output ?: [];
    }

    return new self($params['scheme'], $params['host'], $user, $password, $port, $path, $options);
  }

  public function getScheme(): string
  {
    return $this->scheme;
  }

  public function getHost(): string
  {
    return $this->host;
  }

  public function getUser(): ?string
  {
    return $this->user;
  }

  public function getPassword(): ?string
  {
    return $this->password;
  }

  public function getPort(?int $default = null): ?int
  {
    return $this->port ?? $default;
  }

  public function getPath(?string $default = null): ?string
  {
    return $this->path ?? $default;
  }


  public function getOption(string $key, mixed $default = null): mixed
  {
    return $this->options[$key] ?? $default;
  }


  public function __toString(): string
  {
    $userInfo = $this->user ? rawurlencode($this->user) : '';
    if ($this->password) {
      $userInfo .= ':' . rawurlencode($this->password);
    }

    $port = $this->port ? ':' . $this->port : '';
    $path = $this->path ? '/' . ltrim($this->path, '/') : '';

    $options = '';
    if ($this->options !== []) {
      $optionsArray = [];
      foreach ($this->options as $key => $value) {
        $optionsArray[] = $key . '=' . rawurlencode((string)$value);
      }
      $options = '?' . implode('&', $optionsArray);
    }

    return sprintf(
      '%s://%s%s%s%s%s',
      $this->scheme,
      $userInfo !== '' && $userInfo !== '0' ? $userInfo . '@' : '',
      $this->host,
      $port,
      $path,
      $options
    );
  }
}
