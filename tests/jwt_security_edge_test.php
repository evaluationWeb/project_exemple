<?php

declare(strict_types=1);

// Run: php tests/jwt_security_edge_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

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

function checkJwtEdge(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$secret = str_repeat('k', 48);
$issuer = 'audit-issuer';
$audience = 'audit-audience';
$ttl = 900;
$tokens = new JwtTokenManager($secret, $issuer, $audience, $ttl);

// Invalid configuration must fail before issuing or verifying any token.
foreach ([0, -1, -900] as $invalidTtl) {
    try {
        new JwtTokenManager($secret, $issuer, $audience, $invalidTtl);
        throw new RuntimeException('Invalid JWT TTL unexpectedly accepted: ' . $invalidTtl);
    } catch (InvalidArgumentException $expected) {
        checkJwtEdge(
            str_contains($expected->getMessage(), 'JWT_TTL_SECONDS'),
            'TTL configuration error must identify the setting.'
        );
    }
}
checkJwtEdge((new JwtTokenManager($secret, $issuer, $audience, 1))->expiresIn() === 1, 'Positive TTL rejected.');
checkJwtEdge($tokens->expiresIn() === 900, 'Configured TTL changed.');
$context = new AuthenticationContext();
$router = new Router();
$router->addMiddleware(new RequestAuthenticationMiddleware($context));
$router->setGrantChecker(new ContextGrantChecker($context));
$router->map(Route::get('/api/protected', static fn (): ?int => $context->getUser()?->id, ['ROLE_USER'])
    ->withMiddleware(new JwtMiddleware($tokens, $context)));

$now = time();
$base = [
    'iss' => $issuer,
    'aud' => $audience,
    'sub' => '7',
    'email' => 'user@example.test',
    'roles' => ['ROLE_USER'],
    'iat' => $now - 10,
    'nbf' => $now - 10,
    'exp' => $now + 100,
    'jti' => 'security-test',
];

$tokenFor = static fn (array $claims, ?string $key = null, string $algorithm = 'HS256'): string
    => JWT::encode($claims, $key ?? $secret, $algorithm);
$request = static fn (string $token): Request
    => new Request('/api/protected', 'GET', headers: ['Authorization' => 'Bearer ' . $token]);

checkJwtEdge($router->dispatch($request($tokenFor($base))) === 7, 'Valid role-bearing token rejected.');
checkJwtEdge($context->getUser() === null, 'Authenticated identity persisted after success.');

$invalidClaims = [
    'wrong issuer' => ['iss' => 'other'],
    'wrong audience' => ['aud' => 'other'],
    'invalid subject' => ['sub' => '-7'],
    'zero subject' => ['sub' => '0'],
    'numeric subject' => ['sub' => 7],
    'missing email' => ['email' => null],
    'missing jti' => ['jti' => null],
    'future issued at' => ['iat' => $now + 3600],
    'expired' => ['exp' => $now - 100],
    'expires before issued' => ['exp' => $now - 11],
    'too long lifetime' => ['exp' => $now + 3600],
    'roles is scalar' => ['roles' => 'ROLE_USER'],
    'roles contains non-string' => ['roles' => ['ROLE_USER', 7]],
    'roles contains empty name' => ['roles' => ['']],
    'roles contains very long name' => ['roles' => [str_repeat('R', 101)]],
];

foreach ($invalidClaims as $label => $overrides) {
    $claims = array_replace($base, $overrides);
    try {
        $router->dispatch($request($tokenFor($claims)));
        throw new RuntimeException('Rejected claim unexpectedly accepted: ' . $label);
    } catch (InvalidJwtException $expected) {
        checkJwtEdge($context->getUser() === null, 'Identity leaked after claim rejection: ' . $label);
    }
}

foreach ([
    'wrong secret' => $tokenFor($base, str_repeat('x', 48)),
    'wrong algorithm HS384' => $tokenFor($base, $secret, 'HS384'),
    'corrupted token' => substr($tokenFor($base), 0, -3) . 'xxx',
] as $label => $badToken) {
    try {
        $router->dispatch($request($badToken));
        throw new RuntimeException('Rejected signature unexpectedly accepted: ' . $label);
    } catch (InvalidJwtException $expected) {
        checkJwtEdge($context->getUser() === null, 'Identity leaked after signature rejection: ' . $label);
    }
}

$guestClaims = array_replace($base, ['sub' => '8', 'roles' => []]);
try {
    $router->dispatch($request($tokenFor($guestClaims)));
    throw new RuntimeException('Token without required role unexpectedly accepted.');
} catch (UnauthorizedException $expected) {
    checkJwtEdge($context->getUser() === null, 'Identity leaked after grant rejection.');
}
checkJwtEdge($router->dispatch($request($tokenFor($base))) === 7, 'Authorized identity not restored for next request.');

echo "JWT adversarial claims, signatures, roles and context isolation: OK\n";
