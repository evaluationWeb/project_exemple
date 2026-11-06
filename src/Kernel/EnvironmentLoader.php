<?php

declare(strict_types=1);

namespace App\Kernel;

use Dotenv\Dotenv;

/**
 * Local dotenv files are for development and tests only.
 * Production uses process/server environment variables, never a deployed .env file.
 */
final class EnvironmentLoader
{
    private const VARIABLES = [
        'APP_ENV',
        'DATABASE_HOST',
        'DATABASE_PORT',
        'DATABASE_NAME',
        'DATABASE_USERNAME',
        'DATABASE_PASSWORD',
        'UPLOAD_DIRECTORY',
        'JWT_SECRET',
        'JWT_ISSUER',
        'JWT_AUDIENCE',
        'JWT_TTL_SECONDS',
    ];

    public function load(string $rootPath): void
    {
        $environment = getenv('APP_ENV');
        if ($environment === false || $environment === '') {
            $environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
        }

        if (!in_array($environment, ['dev', 'test', 'prod'], true)) {
            throw new \InvalidArgumentException('APP_ENV must be dev, test or prod.');
        }

        // Populate $_ENV for code that reads it, even when PHP-FPM does not
        // expose process variables through variables_order.
        foreach (self::VARIABLES as $name) {
            $value = getenv($name);
            if ($value !== false) {
                $_ENV[$name] = $value;
            } elseif (isset($_SERVER[$name]) && is_string($_SERVER[$name])) {
                $_ENV[$name] = $_SERVER[$name];
            }
        }
        $_ENV['APP_ENV'] = $environment;

        if ($environment === 'prod') {
            return;
        }

        $file = $environment === 'test' ? '.env.test' : '.env';
        Dotenv::createImmutable($rootPath, $file)->safeLoad();
    }
}
