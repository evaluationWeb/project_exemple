<?php

declare(strict_types=1);

// Run: php tests/csrf_middleware_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Middleware\CsrfMiddleware;
use App\Middleware\InvalidCsrfTokenException;
use App\Security\CsrfTokenManager;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Route;
use Mithridatem\Routing\Router;

function checkMiddleware(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

session_start();
$tokens = new CsrfTokenManager();
$loginToken = $tokens->generate('/login');
$registerToken = $tokens->generate('/register');
// A newly added route needs no registration inside CsrfMiddleware.
$contactToken = $tokens->generate('/contact');
$middleware = new CsrfMiddleware($tokens);
$executions = 0;

$router = new Router();
foreach (['/login', '/register', '/contact'] as $path) {
    $router->map(Route::post($path, static function () use (&$executions): string {
        ++$executions;
        return 'OK';
    })->withMiddleware($middleware));
}

checkMiddleware(
    $router->dispatch(new Request('/login', 'POST', post: ['csrf_token' => $loginToken])) === 'OK',
    'A valid login token must be accepted'
);
checkMiddleware(
    $router->dispatch(new Request('/register', 'POST', post: ['csrf_token' => $registerToken])) === 'OK',
    'A valid register token must be accepted'
);
checkMiddleware(
    $router->dispatch(new Request('/contact', 'POST', post: ['csrf_token' => $contactToken])) === 'OK',
    'A new protected form route must work without modifying CsrfMiddleware'
);
checkMiddleware($executions === 3, 'Expected three successful controller executions');

foreach ([
    ['/login', []],
    ['/login', ['csrf_token' => 'incorrect']],
    ['/login', ['csrf_token' => $registerToken]],
    ['/register', ['csrf_token' => ['invalid']]],
    ['/contact', ['csrf_token' => $loginToken]],
    ['/contact', ['csrf_token' => 'invalid']],
] as [$path, $post]) {
    try {
        $router->dispatch(new Request($path, 'POST', post: $post));
        throw new RuntimeException('Invalid token unexpectedly accepted: ' . $path);
    } catch (InvalidCsrfTokenException $expected) {
        checkMiddleware($executions === 3, 'Controller must not execute on CSRF failure');
    }
}

$router->map(Route::get('/public', static fn (): string => 'public'));
checkMiddleware($router->dispatch(new Request('/public')) === 'public', 'GET routes remain accessible');

session_write_close();
echo "CSRF middleware integration: OK\n";
