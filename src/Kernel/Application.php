<?php

declare(strict_types=1);

namespace App\Kernel;

use Mithridatem\Autowiring\Container;
use Throwable;

/**
 * A small composition root, not a framework kernel.
 * Boot the web environment, construct dependencies, then dispatch.
 */
final class Application
{
    public function __construct(private string $rootPath) {}

    public function run(): void
    {
        (new WebBootstrap())->boot($this->rootPath);

        $container = (new Container())->load(
            $this->rootPath . '/config/service.php'
        );

        $router = (new RouterFactory($container, $this->rootPath))->create();

        try {
            $router->dispatch();
        } catch (Throwable $error) {
            (new WebErrorHandler())->handle($error);
        }
    }
}
