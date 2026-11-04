<?php

declare(strict_types=1);

namespace App\Security\Authentication;

final readonly class AuthenticatedUser
{
    /** @param list<string> $grants */
    public function __construct(
        public int $id,
        public string $email,
        public array $grants
    ) {}
}
