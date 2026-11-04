<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Middleware\ContainerMiddlewareResolver;
use App\Middleware\RequestAuthenticationMiddleware;
use App\Security\Authentication\ContextGrantChecker;
use App\Security\Jwt\InvalidJwtException;
use Mithridatem\Autowiring\Container;
use Mithridatem\Autowiring\Bridge\Routing\ContainerControllerResolver;
use Mithridatem\Routing\Exception\MethodNotAllowedException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');

// Stateless API: no session_start() or cookie-based authentication.
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
$container = (new Container())->load(dirname(__DIR__) . '/config/service.php');

$router = new Router();
$router->setControllerResolver(new ContainerControllerResolver($container));
$router->setMiddlewareResolver(new ContainerMiddlewareResolver($container));
$router->addMiddleware($container->get(RequestAuthenticationMiddleware::class));
$router->setGrantChecker($container->get(ContextGrantChecker::class));

(new AttributeRouteLoader())->loadDirectory(
    router: $router,
    directory: dirname(__DIR__) . '/src/Controller',
    namespace: 'App\\Controller'
);

try {
    $router->dispatch();
} catch (InvalidJwtException $e) {
    http_response_code(401);
    header('WWW-Authenticate: Bearer');
    echo json_encode(['error' => 'Unauthorized']);
} catch (UnauthorizedException $e) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
} catch (RouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
} catch (MethodNotAllowedException $e) {
    http_response_code(405);
    header('Allow: ' . implode(', ', $e->getAllowedMethods()));
    echo json_encode(['error' => 'Method Not Allowed']);
}
