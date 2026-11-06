<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\CsrfTokenManager;
use Mithridatem\Routing\Context\RequestContextInterface;
use Mithridatem\Routing\Http\RequestInterface;
use Mithridatem\Routing\Middleware\MiddlewareInterface;

/**
 * Protects explicitly configured form routes against CSRF.
 *
 * The form identifier is the requested path, not a client-provided form ID.
 * Controllers must generate tokens using the same POST action path.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private CsrfTokenManager $tokens) {}

    public function process(RequestContextInterface $context, callable $next): mixed
    {
        if (!$context instanceof RequestInterface) {
            throw new \LogicException('CsrfMiddleware requires a RequestInterface.');
        }

        if ($context->getMethod() !== 'POST') {
            throw new \LogicException('CsrfMiddleware is intended for POST routes only.');
        }

        $formId = $context->getPath();

        if (!$this->tokens->isValid($formId, $context->post('csrf_token'))) {
            throw new InvalidCsrfTokenException('Token CSRF invalide');
        }

        return $next($context);
    }
}
