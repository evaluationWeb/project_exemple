<?php

declare(strict_types=1);

// Run: php tests/api_kernel_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Kernel\ApiErrorHandler;
use App\Kernel\RouterFactory;
use App\Middleware\JwtMiddleware;
use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Group\GroupedRoute;
use Mithridatem\Routing\Exception\MethodNotAllowedException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use App\Security\Jwt\InvalidJwtException;

function checkApiKernel(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// No database or JWT_SECRET needed to discover routes: token manager is lazy.
$router = (new RouterFactory(new Container(), dirname(__DIR__)))->create();
$login = $router->getRoutes()->match('POST', '/api/login');
$profile = $router->getRoutes()->match('GET', '/api/profile');

checkApiKernel($login !== null && $profile !== null, 'Expected API routes not discovered');
checkApiKernel($login->getRoute() instanceof GroupedRoute, 'Login route must use RouteGroup');
checkApiKernel($profile->getRoute() instanceof GroupedRoute, 'Profile route must use RouteGroup');
checkApiKernel($login->getRoute()->getGrants() === [], 'Public login inherited protected grants');
checkApiKernel($login->getRoute()->getMiddleware() === [], 'API login must be public');
checkApiKernel($profile->getRoute()->getMiddleware() === [JwtMiddleware::class], 'JWT middleware missing from profile');
checkApiKernel($profile->getRoute()->getGrants() === ['ROLE_USER'], 'Profile grants changed');
checkApiKernel($router->getRoutes()->match('GET', '/login') === null, 'Web routes must not be loaded');
checkApiKernel($router->getRoutes()->match('GET', '/register') === null, 'Web routes must not be loaded');
checkApiKernel($router->getRoutes()->match('GET', '/profile') === null, 'Ungrouped profile URL should not exist');

$handler = new ApiErrorHandler();
foreach ([
    [new InvalidJwtException('raw token details'), 401, 'Unauthorized'],
    [new UnauthorizedException('access denied'), 403, 'Forbidden'],
    [new RouteNotFoundException('missing route'), 404, 'Not Found'],
    [new MethodNotAllowedException(['GET', 'POST']), 405, 'Method Not Allowed'],
    [new RuntimeException('database password must stay private'), 500, 'Internal Server Error'],
] as [$error, $expectedStatus, $expectedMessage]) {
    ob_start();
    try {
        $handler->handle($error);
        $body = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
    checkApiKernel(http_response_code() === $expectedStatus, 'Incorrect HTTP status');
    checkApiKernel(
        json_decode($body, true, 512, JSON_THROW_ON_ERROR) === ['error' => $expectedMessage],
        'Incorrect JSON error payload'
    );
    checkApiKernel(
        !str_contains($body, 'token details') && !str_contains($body, 'password'),
        'Internal details exposed'
    );
}

echo "API kernel/bootstrap: OK\n";
