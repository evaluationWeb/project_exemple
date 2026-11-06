<?php

declare(strict_types=1);

namespace App\Kernel;

/**
 * HTTP API bootstrap. Never starts a PHP session or uses cookie authentication.
 */
final class ApiBootstrap
{
    public function boot(string $rootPath): void
    {
        (new EnvironmentLoader())->load($rootPath);

        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('Content-Type: application/json; charset=utf-8');
    }
}
