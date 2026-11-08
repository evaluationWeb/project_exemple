<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Requêtes limitées au propriétaire de l'emprunt.
 * Ne pas exposer de findById() non filtré aux cas d'utilisation utilisateur.
 */
final readonly class LendingRepository
{
    public function __construct(private PDO $connection) {}

    /** @return array<string, mixed>|null */
    public function findByIdAndOwner(int $lendingId, int $ownerId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_lending, lending_at, status, user_id, book_id
             FROM lending WHERE id_lending = :lending AND user_id = :owner'
        );
        $statement->bindValue(':lending', $lendingId, PDO::PARAM_INT);
        $statement->bindValue(':owner', $ownerId, PDO::PARAM_INT);
        $statement->execute();

        $data = $statement->fetch(PDO::FETCH_ASSOC);

        return $data === false ? null : $data;
    }

    /** @return list<array<string, mixed>> */
    public function findAllByOwner(int $ownerId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id_lending, lending_at, status, user_id, book_id
             FROM lending WHERE user_id = :owner ORDER BY id_lending'
        );
        $statement->bindValue(':owner', $ownerId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * L'UPDATE atomique évite une vérification de propriétaire séparée
     * qui pourrait devenir obsolète avant la modification.
     */
    public function markReturnedByOwner(int $lendingId, int $ownerId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE lending SET status = 0
             WHERE id_lending = :lending AND user_id = :owner AND status = 1'
        );
        $statement->bindValue(':lending', $lendingId, PDO::PARAM_INT);
        $statement->bindValue(':owner', $ownerId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() === 1;
    }
}
