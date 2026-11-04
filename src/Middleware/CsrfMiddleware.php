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
 * The form identifier is derived from a fixed, application-controlled route
 * mapping; it is never accepted from an untrusted HTTP field.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const FORMS = [
        '/login' => 'login',
        '/register' => 'register',
    ];

    public function __construct(private CsrfTokenManager $tokens) {}

    public function process(RequestContextInterface $context, callable $next): mixed
    {
        if (!$context instanceof RequestInterface) {
            throw new \LogicException('CsrfMiddleware requires a RequestInterface.');
        }

        if ($context->getMethod() !== 'POST') {
            throw new \LogicException('CsrfMiddleware is intended for POST routes only.');
        }

        $formId = self::FORMS[$context->getPath()] ?? null;
        if ($formId === null) {
            throw new \LogicException('No CSRF form identifier configured for this route.');
        }

        if (!$this->tokens->isValid($formId, $context->post('csrf_token'))) {
            throw new InvalidCsrfTokenException('Token CSRF invalide');
        }

        return $next($context);
    }
}
