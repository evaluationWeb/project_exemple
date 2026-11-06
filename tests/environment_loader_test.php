<?php

declare(strict_types=1);

// Run: php tests/environment_loader_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Kernel\EnvironmentLoader;

function checkEnvironment(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$directory = sys_get_temp_dir() . '/project-env-' . bin2hex(random_bytes(8));
if (!mkdir($directory, 0700) && !is_dir($directory)) {
    throw new RuntimeException('Could not create test directory.');
}

$originalAppEnv = getenv('APP_ENV');
$originalDatabaseHost = getenv('DATABASE_HOST');
$originalServer = $_SERVER;
$originalEnv = $_ENV;

try {
    file_put_contents($directory . '/.env', "DATABASE_HOST=local-dev\n");
    file_put_contents($directory . '/.env.test', "DATABASE_HOST=local-test\n");
    file_put_contents($directory . '/.env.prod', "DATABASE_HOST=never-load-this\n");

    putenv('DATABASE_HOST');
    putenv('APP_ENV=dev');
    unset($_ENV['DATABASE_HOST'], $_SERVER['DATABASE_HOST']);
    (new EnvironmentLoader())->load($directory);
    checkEnvironment($_ENV['DATABASE_HOST'] === 'local-dev', 'Dev must load .env.');

    putenv('DATABASE_HOST=injected-host');
    unset($_ENV['DATABASE_HOST'], $_SERVER['DATABASE_HOST']);
    (new EnvironmentLoader())->load($directory);
    checkEnvironment($_ENV['DATABASE_HOST'] === 'injected-host', 'Injected variables must override local dotenv values.');
    putenv('DATABASE_HOST');

    putenv('APP_ENV=test');
    unset($_ENV['DATABASE_HOST'], $_SERVER['DATABASE_HOST']);
    (new EnvironmentLoader())->load($directory);
    checkEnvironment($_ENV['DATABASE_HOST'] === 'local-test', 'Test must load .env.test.');

    putenv('APP_ENV=prod');
    putenv('DATABASE_HOST=server-prod');
    unset($_ENV['DATABASE_HOST'], $_SERVER['DATABASE_HOST']);
    (new EnvironmentLoader())->load($directory);
    checkEnvironment($_ENV['DATABASE_HOST'] === 'server-prod', 'Production must use server variables.');

    putenv('DATABASE_HOST');
    unset($_ENV['DATABASE_HOST'], $_SERVER['DATABASE_HOST']);
    (new EnvironmentLoader())->load($directory);
    checkEnvironment(!isset($_ENV['DATABASE_HOST']), 'Production must not read .env, .env.test or .env.prod.');

    putenv('APP_ENV=unknown');
    try {
        (new EnvironmentLoader())->load($directory);
        throw new RuntimeException('Invalid APP_ENV unexpectedly accepted.');
    } catch (InvalidArgumentException $expected) {
        // Unknown environments must fail explicitly.
    }

    echo "EnvironmentLoader: OK\n";
} finally {
    foreach (['.env', '.env.test', '.env.prod'] as $name) {
        @unlink($directory . '/' . $name);
    }
    @rmdir($directory);
    if ($originalAppEnv === false) {
        putenv('APP_ENV');
    } else {
        putenv('APP_ENV=' . $originalAppEnv);
    }
    if ($originalDatabaseHost === false) {
        putenv('DATABASE_HOST');
    } else {
        putenv('DATABASE_HOST=' . $originalDatabaseHost);
    }
    $_SERVER = $originalServer;
    $_ENV = $originalEnv;
}
