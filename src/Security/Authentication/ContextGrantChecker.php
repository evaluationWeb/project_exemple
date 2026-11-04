<?php

declare(strict_types=1);

namespace App\Security\Authentication;

use Mithridatem\Routing\Auth\ArrayGrantChecker;
use Mithridatem\Routing\Auth\GrantCheckerInterface;

/** API-only grants: never trust PHP sessions or cookies. */
final class ContextGrantChecker implements GrantCheckerInterface
{
    public function __construct(private AuthenticationContext $context) {}

    public function isGranted(array $requiredGrants): bool
    {
        return (new ArrayGrantChecker($this->context->getGrants()))
            ->isGranted($requiredGrants);
    }
}
