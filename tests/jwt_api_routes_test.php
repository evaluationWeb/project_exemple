<?php

declare(strict_types=1);

// Run: php tests/jwt_api_routes_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\Api\AuthController;
use App\Controller\Api\ProfileController;
use App\Middleware\JwtMiddleware;
use Mithridatem\Routing\Loader\AttributeRouteLoader;
use Mithridatem\Routing\Router;

$router = new Router();
(new AttributeRouteLoader())->loadControllers($router, [
    AuthController::class,
    ProfileController::class,
]);

$login = $router->getRoutes()->match('POST', '/api/login');
$profile = $router->getRoutes()->match('GET', '/api/profile');
if ($login === null || $profile === null) {
    throw new RuntimeException('JWT API endpoints were not registered.');
}
if ($login->getRoute()->getMiddleware() !== [] || $login->getRoute()->getGrants() !== []) {
    throw new RuntimeException('API login must be public.');
}
if ($profile->getRoute()->getMiddleware() !== [JwtMiddleware::class]) {
    throw new RuntimeException('API profile must run JWT middleware.');
}
if ($profile->getRoute()->getGrants() !== ['ROLE_USER']) {
    throw new RuntimeException('API profile must require ROLE_USER.');
}
echo "JWT API route declarations: OK\n";
