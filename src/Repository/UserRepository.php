<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Repository\AbstractRepository;
use App\Entity\User;
use Override;
use PDO;

final class UserRepository extends AbstractRepository
{
    public function __construct(PDO $connect)
    {
        return parent::__construct($connect);
    }

    #[Override]
    public function find(int $id): ?User
    {
        $sql = $sql = <<<SQL
            SELECT u.id, u.firstname, u.lastname, u.email, 
            u.hash_password, u.created_at, u.updated_at, 
            u.img_profil, u.`grant` FROM `user` AS u 
            WHERE u.id = ?
            ORDER BY u.id
            SQL;
        try {
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        return $user ?? null;
    }

    #[Override]
    public function findAll(): array
    {
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email, 
            u.img_profil FROM `user` AS u ORDER BY u.id
            SQL;
        try {
            $users = $this->connect
                ->query($sql)
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        return $users ?? [];
    }

    #[Override]
    public function create(Entity $entity): ?User
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('UserRepository attend une entité User.');
        }

        $sql = <<<SQL
            INSERT INTO `user`(firstname, lastname, email, hash_password, created_at,
            img_profil, `grant`) VALUES (?,?,?,?,?,?,?)
            SQL;

        try {
            $stmt = $this->connect->prepare($sql);
            $stmt->execute([
                $entity->getFirstname(),
                $entity->getLastname(),
                $entity->getEmail(),
                $entity->getHashPassword(),
                $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                $entity->getImgProfil(),
                $entity->getGrant(),
            ]);

            $entity->setId((int) $this->connect->lastInsertId());
            return $entity;
        } catch (\PDOException $e) {
            // Laisser le service distinguer échec et succès sans afficher une erreur SQL.
            return null;
        }
    }

    public function findByEmail(string $email): ?User
    {
        $sql = <<<SQL
            SELECT u.id_user, u.firstname, u.lastname, u.email, 
            u.hash_password, u.created_at, u.updated_at, 
            u.img_profil, u.`grant` FROM `user` AS u 
            WHERE u.email = ?
            SQL;
        try {
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(1, $email, \PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch();
            if ($data) $user = User::hydrate($data);
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return null;
        }
        return $user ?? null;
    }
}
