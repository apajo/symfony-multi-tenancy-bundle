<?php

namespace aPajo\MultiTenancyBundle\Adapter;

use Symfony\Component\Mailer\Exception\InvalidArgumentException;

final class Dsn
{
  private string $scheme;
  private string $host;
  private ?string $user;
  private ?string $password;
  private ?int $port;
  private ?string $path;
  private array $options;

  public function __construct(string $scheme, string $host, ?string $user = null, #[\SensitiveParameter] ?string $password = null, ?int $port = null, ?string $path = null, array $options = [])
  {
    $this->scheme = $scheme;
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
    $this->port = $port;
    $this->path = $path;
    $this->options = $options;
  }

  public static function fromString(#[\SensitiveParameter] string $dsn): self
  {
    if (false === $params = parse_url($dsn)) {
      throw new InvalidArgumentException('The mailer DSN is invalid.');
    }

    if (!isset($params['scheme'])) {
      throw new InvalidArgumentException('The mailer DSN must contain a scheme.');
    }

    if (!isset($params['host'])) {
      throw new InvalidArgumentException('The mailer DSN must contain a host (use "default" by default).');
    }

    $user = '' !== ($params['user'] ?? '') ? rawurldecode($params['user']) : null;
    $password = '' !== ($params['pass'] ?? '') ? rawurldecode($params['pass']) : null;
    $port = $params['port'] ?? null;
    $path = $params['path'] ?? null;

    return new self($params['scheme'], $params['host'], $user, $password, $port, $path);
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
    if (!empty($this->options)) {
      $optionsArray = [];
      foreach ($this->options as $key => $value) {
        $optionsArray[] = $key . '=' . rawurlencode((string) $value);
      }
      $options = '?' . implode('&', $optionsArray);
    }

    return sprintf(
      '%s://%s%s%s%s%s',
      $this->scheme,
      $userInfo ? $userInfo . '@' : '',
      $this->host,
      $port,
      $path,
      $options
    );
  }
}
