<?php

declare(strict_types=1);

// Lancer : php tests/auth_form_render_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\LoginController;
use App\Controller\RegisterController;
use App\Security\CsrfTokenManager;
use Mithridatem\Routing\Http\RequestInterface;

function assertForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function formRequest(string $method, array $post = []): RequestInterface
{
    return new class($method, $post) implements RequestInterface {
        public function __construct(private string $method, private array $post) {}
        public function getMethod(): string { return $this->method; }
        public function getPath(): string { return '/'; }
        public function getBearerToken(): ?string { return null; }
        public function query(string $name, mixed $default = null): mixed { return $default; }
        public function post(string $name, mixed $default = null): mixed { return $this->post[$name] ?? $default; }
        public function header(string $name, ?string $default = null): ?string { return $default; }
        public function getContent(): string { return ''; }
    };
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

$loginToken = $csrf->generate('login');
$registerToken = $csrf->generate('register');
assertForm($loginToken !== $registerToken, 'Les formulaires doivent avoir des tokens distincts');

$loginGet = captureForm(fn() => $login->showLogin());
assertForm(str_contains($loginGet, 'value="' . $loginToken . '"'), 'GET login : token manquant');

$loginInvalid = captureForm(fn() => $login->login(formRequest('POST', ['csrf_token' => ['invalid']])));
assertForm(str_contains($loginInvalid, 'Token CSRF invalide'), 'POST login : erreur CSRF manquante');
assertForm(str_contains($loginInvalid, 'value="' . $loginToken . '"'), 'POST login : token absent après erreur');

$registerGet = captureForm(fn() => $register->register(formRequest('GET')));
assertForm(str_contains($registerGet, 'value="' . $registerToken . '"'), 'GET register : token manquant');

$registerInvalid = captureForm(fn() => $register->register(formRequest('POST', ['csrf_token' => 'invalid'])));
assertForm(str_contains($registerInvalid, 'Token CSRF invalide'), 'POST register : erreur CSRF manquante');
assertForm(str_contains($registerInvalid, 'value="' . $registerToken . '"'), 'POST register : token absent après erreur');

session_write_close();
echo "Auth form rendering : OK\n";
