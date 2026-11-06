<?php

declare(strict_types=1);

// Run: php tests/jwt_authentication_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Entity\User;
use App\Middleware\JwtMiddleware;
use App\Middleware\RequestAuthenticationMiddleware;
use App\Security\Authentication\AuthenticationContext;
use App\Security\Authentication\ContextGrantChecker;
use App\Security\Jwt\InvalidJwtException;
use App\Security\Jwt\JwtTokenManager;
use Firebase\JWT\JWT;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Route;
use Mithridatem\Routing\Router;

function verifyJwt(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$secret = str_repeat('a', 48);
$tokens = new JwtTokenManager($secret, 'project-exemple', 'project-exemple-api', 900);
$context = new AuthenticationContext();
$router = new Router();
$router->setGrantChecker(new ContextGrantChecker($context));
$router->addMiddleware(new RequestAuthenticationMiddleware($context));
$user = new User('Test', 'User', 'test@example.test', 'irrelevant');
$user->setId(42)->setGrant('ROLE_USER');
$jwt = $tokens->issue($user);

$router->map(Route::get('/api/profile', static function () use ($context): string {
    return (string) $context->getUser()?->id;
}, ['ROLE_USER'])->withMiddleware(new JwtMiddleware($tokens, $context)));
$request = static fn (?string $jwt): Request => new Request('/api/profile', 'GET', headers: $jwt === null
    ? []
    : ['Authorization' => 'Bearer ' . $jwt]);

verifyJwt($router->dispatch($request($jwt)) === '42', 'Valid JWT must authenticate and authorize.');
verifyJwt($context->getUser() === null, 'Context must be reset after request.');

$expectAuthFailure = static function (Request $request) use ($router, $context): void {
    try {
        $router->dispatch($request);
        throw new RuntimeException('Invalid JWT unexpectedly accepted.');
    } catch (InvalidJwtException $expected) {
        verifyJwt($context->getUser() === null, 'Identity leaked on authentication failure.');
    }
};

$expectAuthFailure($request(null));
$expectAuthFailure($request(substr($jwt, 0, -2) . 'xx'));
$expectAuthFailure($request(JWT::encode([
    'sub' => '42', 'roles' => ['ROLE_USER'],
    'email' => 'test@example.test',
    'iss' => 'other-issuer', 'aud' => 'project-exemple-api',
    'iat' => time(), 'nbf' => time(), 'exp' => time() + 100,
    'jti' => 'test',
], $secret, 'HS256')));
$expectAuthFailure($request(JWT::encode([
    'sub' => '42', 'roles' => ['ROLE_USER'],
    'email' => 'test@example.test',
    'iss' => 'project-exemple', 'aud' => 'project-exemple-api',
    'iat' => time() - 1800, 'nbf' => time() - 1800, 'exp' => time() - 100,
    'jti' => 'test',
], $secret, 'HS256')));
$expectAuthFailure($request(JWT::encode([
    'sub' => '42', 'roles' => ['ROLE_USER'],
    'email' => 'test@example.test',
    'iss' => 'project-exemple', 'aud' => 'wrong-audience',
    'iat' => time(), 'nbf' => time(), 'exp' => time() + 100,
    'jti' => 'test',
], $secret, 'HS256')));

// A valid JWT is always required on the protected API route.
$expectAuthFailure($request(null));

$noRole = JWT::encode([
    'sub' => '42', 'roles' => [],
    'email' => 'test@example.test',
    'iss' => 'project-exemple', 'aud' => 'project-exemple-api',
    'iat' => time(), 'nbf' => time(), 'exp' => time() + 100,
    'jti' => 'test',
], $secret, 'HS256');
try {
    $router->dispatch($request($noRole));
    throw new RuntimeException('Missing role unexpectedly accepted.');
} catch (UnauthorizedException $expected) {
    verifyJwt($context->getUser() === null, 'Context was not reset on grant failure.');
}

// A second user must not inherit the first user's identity.
$other = new User('Test', 'Other', 'other@example.test', 'irrelevant');
$other->setId(67)->setGrant('ROLE_USER');
verifyJwt($router->dispatch($request($tokens->issue($other))) === '67', 'Stale JWT identity reused.');

echo "JWT authentication and grants: OK\n";
