<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Middleware\ContainerMiddlewareResolver;
use Mithridatem\Autowiring\Bridge\Routing\ContainerControllerResolver;
use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Auth\ArrayGrantChecker;
use Mithridatem\Routing\Auth\AuthenticationAwareGrantChecker;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

/**
 * Assembly of the application's router only; business routes remain Attributes.
 */
final class RouterFactory
{
    public function __construct(
        private Container $container,
        private string $rootPath
    ) {}

    public function create(): Router
    {
        $router = new Router();
        $router->setControllerResolver(new ContainerControllerResolver($this->container));
        $router->setMiddlewareResolver(new ContainerMiddlewareResolver($this->container));
        $router->setGrantChecker(
            new AuthenticationAwareGrantChecker(
                static fn (): bool => isset($_SESSION['user']['id']),
                new ArrayGrantChecker($_SESSION['user']['grant'] ?? [])
            )
        );

        (new AttributeRouteLoader())->loadDirectory(
            router: $router,
            directory: $this->rootPath . '/src/Controller',
            namespace: 'App\\Controller'
        );

        return $router;
    }
}
