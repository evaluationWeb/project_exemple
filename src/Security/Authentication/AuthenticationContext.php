<?php

declare(strict_types=1);

namespace App\Security\Authentication;

/**
 * Request-scoped identity. The front controller creates a fresh container for
 * every HTTP request. RequestAuthenticationMiddleware also resets it for
 * repeated dispatches (e.g. in integration tests or a future long-running app).
 */
final class AuthenticationContext
{
    private ?AuthenticatedUser $user = null;

    public function reset(): void
    {
        $this->user = null;
    }

    public function authenticate(AuthenticatedUser $user): void
    {
        if ($this->user !== null) {
            throw new \LogicException('Authentication context is already initialized.');
        }

        $this->user = $user;
    }

    public function getUser(): ?AuthenticatedUser
    {
        return $this->user;
    }

    /** @return list<string> */
    public function getGrants(): array
    {
        return $this->user?->grants ?? [];
    }
}
