<?php

declare(strict_types=1);

namespace App\Security\Authentication;

use Mithridatem\Routing\Auth\ArrayGrantChecker;
use Mithridatem\Routing\Auth\GrantCheckerInterface;

/**
 * JWT grants take precedence whenever an authenticated JWT identity exists.
 * For ordinary web routes, grants continue coming from the PHP session.
 * A missing/invalid JWT is rejected by JwtMiddleware before reaching here.
 */
final class ContextGrantChecker implements GrantCheckerInterface
{
    public function __construct(private AuthenticationContext $context) {}

    public function isGranted(array $requiredGrants): bool
    {
        $grants = $this->context->getUser() !== null
            ? $this->context->getGrants()
            : ($_SESSION['user']['grant'] ?? []);

        return (new ArrayGrantChecker(is_array($grants) ? $grants : []))
            ->isGranted($requiredGrants);
    }
}
