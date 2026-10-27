<?php

/**
 * PHPUnit bootstrap for the bundle's own test suite.
 *
 * This bundle has no host Symfony application; tests/TestKernel.php stands in
 * for one. All this file does is register the autoloader and populate the env
 * vars that config/packages/test/*.yaml reference.
 *
 * Precedence, highest first:
 *   1. real environment variables (e.g. DEFAULT_DATABASE_URL=... vendor/bin/phpunit)
 *   2. .env.test.local   (gitignored, for local overrides)
 *   3. .env.test         (committed test-only overrides)
 *   4. .env              (committed defaults, shared with bin/console)
 *   5. the hardcoded fallbacks at the bottom of this file
 */

use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

// load() does not override variables that are already set, so listing the
// local file first gives it precedence over the committed one.
$envFiles = array_filter([
    $root . '/.env.test.local',
    $root . '/.env.test',
    $root . '/.env',
], 'is_file');

if ($envFiles !== [] && class_exists(Dotenv::class)) {
    (new Dotenv())->load(...$envFiles);
}

// Fallbacks, so the unit and integration suites run even with no env files at
// all. Mirrors .env; DATABASE_HOST is the one part compose swaps out.
$databaseHost = $_SERVER['DATABASE_HOST'] ?? $_ENV['DATABASE_HOST'] ?? (getenv('DATABASE_HOST') ?: '127.0.0.1');
$databasePort = $_SERVER['DATABASE_PORT'] ?? $_ENV['DATABASE_PORT'] ?? (getenv('DATABASE_PORT') ?: '49173');

$defaults = [
    'APP_ENV' => 'test',
    'APP_DEBUG' => '1',
    'APP_SECRET' => 'test-secret-not-used-outside-tests',
    'MAILER_DSN' => 'null://null',
    'DATABASE_HOST' => $databaseHost,
    'DATABASE_PORT' => $databasePort,
    'DEFAULT_DATABASE_URL' => sprintf('mysql://mtb:mtb@%s:%s/mtb_default_test', $databaseHost, $databasePort),
    'TENANT_DATABASE_URL' => sprintf('mysql://mtb:mtb@%s:%s/mtb_tenant_test', $databaseHost, $databasePort),
];

foreach ($defaults as $key => $value) {
    $_SERVER[$key] ??= $_ENV[$key] ?? $value;
    $_ENV[$key] ??= $_SERVER[$key];

    if (getenv($key) === false) {
        putenv(sprintf('%s=%s', $key, $_SERVER[$key]));
    }
}
