<?php

declare(strict_types=1);

// Lancer : php tests/auth_form_render_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\LoginController;
use App\Controller\RegisterController;
use App\Security\CsrfTokenManager;
use Mithridatem\Routing\Http\Request;

function assertForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function formRequest(string $method, array $post = []): Request
{
    return new Request('/', $method, post: $post);
}

function controllerForRender(string $class, CsrfTokenManager $csrf): object
{
    // Les chemins testés n'appellent ni LoginService ni UserService :
    // on évite de construire leur graphe de dépendances SQL.
    $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    (new ReflectionProperty($class, 'csrf'))->setValue($controller, $csrf);

    return $controller;
}

function captureForm(callable $callback): string
{
    ob_start();
    try {
        $callback();
        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

session_start();
$csrf = new CsrfTokenManager();
$login = controllerForRender(LoginController::class, $csrf);
$register = controllerForRender(RegisterController::class, $csrf);

$loginToken = $csrf->generate('/login');
$registerToken = $csrf->generate('/register');
assertForm($loginToken !== $registerToken, 'Les formulaires doivent avoir des tokens distincts');

$loginGet = captureForm(fn() => $login->showLogin());
assertForm(str_contains($loginGet, 'value="' . $loginToken . '"'), 'GET login : token manquant');

$registerGet = captureForm(fn() => $register->register(formRequest('GET')));
assertForm(str_contains($registerGet, 'value="' . $registerToken . '"'), 'GET register : token manquant');

session_write_close();
echo "Auth form rendering : OK\n";
