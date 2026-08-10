<?php

namespace aPajo\MultiTenancyBundle\Tests\Unit\Adapter;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dsn::class)]
class DsnTest extends TestCase
{
  public function testFromStringParsesEveryComponent(): void
  {
    $dsn = Dsn::fromString('mysql://alice:s3cret@db.example.com:3307/tenant_one?charset=utf8mb4&serverVersion=8.0');

    self::assertSame('mysql', $dsn->getScheme());
    self::assertSame('db.example.com', $dsn->getHost());
    self::assertSame('alice', $dsn->getUser());
    self::assertSame('s3cret', $dsn->getPassword());
    self::assertSame(3307, $dsn->getPort());
    self::assertSame('/tenant_one', $dsn->getPath());
    self::assertSame('utf8mb4', $dsn->getOption('charset'));
    self::assertSame('8.0', $dsn->getOption('serverVersion'));
  }

  public function testFromStringRawUrlDecodesCredentials(): void
  {
    $dsn = Dsn::fromString('smtp://user%40example.com:p%40ss%20word@mail.example.com:587');

    self::assertSame('user@example.com', $dsn->getUser());
    self::assertSame('p@ss word', $dsn->getPassword());
    self::assertSame(587, $dsn->getPort());
  }

  public function testMinimalDsnLeavesOptionalComponentsNull(): void
  {
    $dsn = Dsn::fromString('mysql://localhost');

    self::assertSame('mysql', $dsn->getScheme());
    self::assertSame('localhost', $dsn->getHost());
    self::assertNull($dsn->getUser());
    self::assertNull($dsn->getPassword());
    self::assertNull($dsn->getPort());
    self::assertNull($dsn->getPath());
  }

  public function testEmptyCredentialsBecomeNullRatherThanEmptyString(): void
  {
    // parse_url reports user/pass as '' here; Dsn normalises both to null so
    // callers can rely on a single "absent" representation.
    $dsn = Dsn::fromString('mysql://@localhost');

    self::assertNull($dsn->getUser());
    self::assertNull($dsn->getPassword());
  }

  public function testGettersFallBackToSuppliedDefaults(): void
  {
    $dsn = Dsn::fromString('mysql://localhost');

    self::assertSame(3306, $dsn->getPort(3306));
    self::assertSame('/fallback', $dsn->getPath('/fallback'));
    self::assertSame('utf8', $dsn->getOption('charset', 'utf8'));
  }

  public function testExplicitValuesWinOverDefaults(): void
  {
    $dsn = Dsn::fromString('mysql://localhost:3307/db?charset=latin1');

    self::assertSame(3307, $dsn->getPort(3306));
    self::assertSame('/db', $dsn->getPath('/fallback'));
    self::assertSame('latin1', $dsn->getOption('charset', 'utf8'));
  }

  public function testQueryStringWithoutValuesYieldsEmptyOption(): void
  {
    $dsn = Dsn::fromString('mysql://localhost/db?flag');

    self::assertSame('', $dsn->getOption('flag'));
    self::assertNull($dsn->getOption('missing'));
  }

  public function testUnparseableDsnIsRejected(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The DSN is invalid.');

    Dsn::fromString('http://:80');
  }

  #[DataProvider('schemelessDsnProvider')]
  public function testSchemeIsRequired(string $dsn): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The DSN must contain a scheme.');

    Dsn::fromString($dsn);
  }

  public static function schemelessDsnProvider(): iterable
  {
    yield 'bare path' => ['localhost/database'];
    yield 'protocol relative' => ['//localhost'];
  }

  public function testHostIsRequired(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The DSN must contain a host');

    // Has a scheme, but parse_url reports no host component.
    Dsn::fromString('mailto:tenant');
  }

  #[DataProvider('roundTripProvider')]
  public function testToStringRoundTrips(string $dsn): void
  {
    self::assertSame($dsn, (string) Dsn::fromString($dsn));
  }

  public static function roundTripProvider(): iterable
  {
    yield 'host only' => ['mysql://localhost'];
    yield 'host and port' => ['mysql://localhost:3307'];
    yield 'host and path' => ['mysql://localhost/tenant_one'];
    yield 'user only' => ['mysql://alice@localhost/tenant_one'];
    yield 'full' => ['mysql://alice:s3cret@localhost:3307/tenant_one'];
    yield 'single option' => ['mysql://localhost/tenant_one?charset=utf8mb4'];
    yield 'null transport' => ['null://null'];
  }

  public function testToStringReEncodesCredentials(): void
  {
    $dsn = new Dsn('smtp', 'mail.example.com', 'user@example.com', 'p@ss word', 587);

    self::assertSame('smtp://user%40example.com:p%40ss%20word@mail.example.com:587', (string) $dsn);
  }

  public function testConstructorAcceptsComponentsDirectly(): void
  {
    $dsn = new Dsn('mysql', 'localhost', null, null, 3306, '/tenant_one', ['charset' => 'utf8mb4']);

    self::assertSame('mysql://localhost:3306/tenant_one?charset=utf8mb4', (string) $dsn);
    self::assertSame('utf8mb4', $dsn->getOption('charset'));
  }

  public function testToStringNormalisesPathWithoutLeadingSlash(): void
  {
    $dsn = new Dsn('mysql', 'localhost', path: 'tenant_one');

    self::assertSame('mysql://localhost/tenant_one', (string) $dsn);
  }
}
