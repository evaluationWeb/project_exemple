<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Security\GuestNavigation;
use Mithridatem\Routing\Auth\ArrayGrantChecker;
use Mithridatem\Routing\Auth\AuthenticationAwareGrantChecker;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Route;
use Mithridatem\Routing\Router;

function ensureNavigation(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

session_start();
$router = new Router();
$router->setGrantChecker(new AuthenticationAwareGrantChecker(
    static fn (): bool => isset($_SESSION['user']['id']),
    new ArrayGrantChecker(['ROLE_USER'])
));
$router->map(Route::get('/', static fn (): string => 'home'));
$router->map(Route::get('/books', static fn (): string => 'books', ['ROLE_USER']));
$router->map(Route::get('/admin', static fn (): string => 'admin', ['ROLE_ADMIN']));
$router->map(Route::get('/login', static fn (): string => 'login', ['IS_GUEST']));
$router->map(Route::post('/login', static fn (): string => 'login', ['IS_GUEST']));
$router->map(Route::get('/register', static fn (): string => 'register', ['IS_GUEST']));

$navigation = new GuestNavigation();
$_SESSION['user'] = ['id' => 42, 'grant' => ['ROLE_USER']];
$navigation->rememberSuccessfulGet(new Request('/books', 'GET'), $router);
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === '/books',
    'A logged-in user should return to the previously visited allowed page.'
);
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'POST'), $router) === '/books',
    'POST login should also redirect when authenticated.'
);
$navigation->rememberSuccessfulGet(new Request('/register', 'GET'), $router);
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === '/books',
    'Guest routes must never become the previous page.'
);
$navigation->rememberSuccessfulGet(new Request('/admin', 'GET'), $router);
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === '/books',
    'Unauthorized GET routes must not overwrite the previous page.'
);
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/admin', 'GET'), $router) === null,
    'A normal role denial must not redirect.'
);
$_SESSION['_last_safe_page'] = '//outside.example/path';
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === '/',
    'External redirection attempts must fall back to /.'
);
$_SESSION['_last_safe_page'] = '/admin';
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === '/',
    'An inaccessible previous route must fall back to /.'
);
$_SESSION['_last_safe_page'] = '/login';
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === '/',
    'A guest page must not cause a loop.'
);
unset($_SESSION['user']);
ensureNavigation(
    $navigation->destinationForDeniedGuest(new Request('/login', 'GET'), $router) === null,
    'Unauthenticated guests should not redirect.'
);
$navigation->rememberSuccessfulGet(new Request('/', 'POST'), $router);
try {
    new GuestNavigation('//outside.example');
    throw new RuntimeException('Invalid fallbacks must be rejected.');
} catch (InvalidArgumentException $expected) {
}
$_SESSION = [];
session_write_close();
echo "Guest navigation: OK\n";
