<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\Authentication\AuthenticationContext;
use App\Security\Jwt\InvalidJwtException;
use App\Security\Jwt\JwtTokenManager;
use Mithridatem\Routing\Context\RequestContextInterface;
use Mithridatem\Routing\Middleware\MiddlewareInterface;

final class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtTokenManager $tokens,
        private AuthenticationContext $context
    ) {}

    public function process(RequestContextInterface $request, callable $next): mixed
    {
        $token = $request->getBearerToken();
        if ($token === null || $token === '') {
            throw new InvalidJwtException('Bearer token required.');
        }

        $this->context->authenticate($this->tokens->authenticate($token));

        return $next($request);
    }
}
