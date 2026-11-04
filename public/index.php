<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Middleware\ContainerMiddlewareResolver;
use App\Middleware\RequestAuthenticationMiddleware;
use App\Security\Authentication\AuthenticationContext;
use App\Security\Authentication\ContextGrantChecker;
use App\Security\Jwt\InvalidJwtException;
use App\Middleware\InvalidCsrfTokenException;
use Mithridatem\Autowiring\Container;
use Mithridatem\Autowiring\Bridge\Routing\ContainerControllerResolver;
use Mithridatem\Routing\Exception\RouterException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

//Headers sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; script-src 'self'; object-src 'none'; frame-ancestors 'none';");

//Configuration session
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.use_strict_mode', '1');
session_start();

//importer les variables d'environnement 
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Charger les dépendances de l'application.
$container = (new Container())->load(
    dirname(__DIR__) . '/config/service.php'
);

// Le routeur délègue la construction des contrôleurs au conteneur.
$router = new Router();
$router->setControllerResolver(
    new ContainerControllerResolver($container)
);
$router->setMiddlewareResolver(new ContainerMiddlewareResolver($container));
// A new container is constructed for each PHP-FPM request; the global middleware
// additionally resets its context for repeated dispatches in the same process.
$router->addMiddleware($container->get(RequestAuthenticationMiddleware::class));

// Conserver ici les éventuels réglages de basePath et d'autorisations.
$router->setGrantChecker(
    $container->get(ContextGrantChecker::class)
);

// Charger les routes déclarées dans les contrôleurs avec #[Route].
(new AttributeRouteLoader())->loadDirectory(
    router: $router,
    directory: dirname(__DIR__) . '/src/Controller',
    namespace: 'App\\Controller'
);
//Dispatch des routes
try {
    $router->dispatch();
} catch (InvalidJwtException $e) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('WWW-Authenticate: Bearer');
    echo json_encode(['error' => 'Unauthorized']);
} catch (InvalidCsrfTokenException $e) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>403 — Requête refusée</h1><p>' .
        htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
        '</p>';
} catch (RouterException $e) {
    echo $e->getMessage();
} catch (RouteNotFoundException $e) {
    echo $e->getMessage();
} catch (UnauthorizedException $e) {
    http_response_code(403);
    // API access denies are JSON; existing HTML route behavior remains a text message.
    if (str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden']);
    } else {
        echo $e->getMessage();
    }
}
