<?php

declare(strict_types=1);

namespace App\Kernel;

use Mithridatem\Autowiring\Container;
use Throwable;

/**
 * Lightweight application composition root — not a new framework kernel.
 */
final class Application
{
    public function __construct(private string $rootPath) {}

    public function run(): void
    {
        (new ApiBootstrap())->boot($this->rootPath);

        try {
            $container = (new Container())->load(
                $this->rootPath . '/config/service.php'
            );
            $router = (new RouterFactory($container, $this->rootPath))->create();
            $router->dispatch();
        } catch (Throwable $error) {
            (new ApiErrorHandler())->handle($error);
        }
    }
}
