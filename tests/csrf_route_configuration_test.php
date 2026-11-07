<?php

declare(strict_types=1);

// Run: php tests/csrf_route_configuration_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\LoginController;
use App\Controller\RegisterController;
use App\Middleware\CsrfMiddleware;
use App\Middleware\InvalidCsrfTokenException;
use App\Security\CsrfTokenManager;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Middleware\MiddlewareInterface;
use Mithridatem\Routing\Middleware\MiddlewareResolverInterface;
use Mithridatem\Routing\Router;
use Mithridatem\Routing\Group\GroupedRoute;

session_start();
$router = new Router();
$loader = new AttributeRouteLoader();
$loader->loadControllers($router, [LoginController::class, RegisterController::class]);

$tokens = new CsrfTokenManager();
$router->setMiddlewareResolver(new class($tokens) implements MiddlewareResolverInterface {
    public function __construct(private CsrfTokenManager $tokens) {}

    public function resolve(string $middleware): MiddlewareInterface
    {
        if ($middleware !== CsrfMiddleware::class) {
            throw new LogicException('Unexpected middleware: ' . $middleware);
        }

        return new CsrfMiddleware($this->tokens);
    }
});

foreach (['/login', '/register'] as $path) {
    $get = $router->getRoutes()->match('GET', $path);
    $post = $router->getRoutes()->match('POST', $path);
    if ($get === null || $post === null) {
        throw new RuntimeException('Expected GET and POST routes for ' . $path);
    }
    if (!$get->getRoute() instanceof GroupedRoute || !$post->getRoute() instanceof GroupedRoute) {
        throw new RuntimeException('Expected grouped authentication routes: ' . $path);
    }
    if ($get->getRoute()->getGrants() !== ['IS_GUEST'] || $post->getRoute()->getGrants() !== ['IS_GUEST']) {
        throw new RuntimeException('Authentication routes must be guest-only: ' . $path);
    }
    if ($get->getRoute()->getMiddleware() !== []) {
        throw new RuntimeException('GET must not require CSRF middleware: ' . $path);
    }
    if ($post->getRoute()->getMiddleware() !== [CsrfMiddleware::class]) {
        throw new RuntimeException('POST must require CSRF middleware: ' . $path);
    }

    try {
        $router->dispatch(new Request($path, 'POST', post: ['csrf_token' => 'bad']));
        throw new RuntimeException('Route accepted invalid CSRF token: ' . $path);
    } catch (InvalidCsrfTokenException $expected) {
        // Controller resolution must not be reached on invalid CSRF.
    }
}

session_write_close();
echo "CSRF route configuration: OK\n";
