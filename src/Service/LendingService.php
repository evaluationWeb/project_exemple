<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\LendingRepository;
use App\Service\Exception\LendingNotFoundException;

/**
 * Cas d'utilisation indépendants de HTTP.
 * ownerId doit être obtenu à partir de la session authentifiée ou du JWT vérifié.
 */
final readonly class LendingService
{
    public function __construct(private LendingRepository $lendings) {}

    /** @return array<string, mixed> */
    public function show(int $lendingId, int $ownerId): array
    {
        self::assertValidIdentifiers($lendingId, $ownerId);

        return $this->lendings->findByIdAndOwner($lendingId, $ownerId)
            ?? throw new LendingNotFoundException();
    }

    /** @return list<array<string, mixed>> */
    public function listMine(int $ownerId): array
    {
        self::assertValidIdentifiers($ownerId);

        return $this->lendings->findAllByOwner($ownerId);
    }

    public function returnBook(int $lendingId, int $ownerId): void
    {
        self::assertValidIdentifiers($lendingId, $ownerId);

        if (!$this->lendings->markReturnedByOwner($lendingId, $ownerId)) {
            // Ressource absente, étrangère ou déjà rendue : même réponse externe.
            throw new LendingNotFoundException();
        }
    }

    private static function assertValidIdentifiers(int ...$identifiers): void
    {
        foreach ($identifiers as $identifier) {
            if ($identifier <= 0) {
                throw new LendingNotFoundException();
            }
        }
    }
}
