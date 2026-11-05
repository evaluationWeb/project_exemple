<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Middleware\ContainerMiddlewareResolver;
use App\Middleware\RequestAuthenticationMiddleware;
use App\Security\Authentication\ContextGrantChecker;
use Mithridatem\Autowiring\Bridge\Routing\ContainerControllerResolver;
use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

/** Configures only the API routing pipeline and controllers. */
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

        // First middleware initializes and clears identity even if dispatch fails.
        $router->addMiddleware(
            $this->container->get(RequestAuthenticationMiddleware::class)
        );
        $router->setGrantChecker(
            $this->container->get(ContextGrantChecker::class)
        );

        (new AttributeRouteLoader())->loadDirectory(
            router: $router,
            directory: $this->rootPath . '/src/Controller',
            namespace: 'App\\Controller'
        );

        return $router;
    }
}
