<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\Authentication\AuthenticationContext;
use Mithridatem\Routing\Context\RequestContextInterface;
use Mithridatem\Routing\Middleware\MiddlewareInterface;

/** Must run as the FIRST global middleware. */
final class RequestAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthenticationContext $context) {}

    public function process(RequestContextInterface $request, callable $next): mixed
    {
        $this->context->reset();

        try {
            return $next($request);
        } finally {
            $this->context->reset();
        }
    }
}
