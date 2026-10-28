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
        $sql = <<<SQL
            INSERT INTO `user`(firstname, lastname, email, hash_password, created_at, 
            img_profil, `grant`) VALUE (?,?,?,?,?,?,?)
            SQL;
        try {
            $stmt = $this->connect->prepare($sql);
            $stmt->bindValue(1, $entity->getFirstname(), \PDO::PARAM_STR);
            $stmt->bindValue(2, $entity->getlastname(), \PDO::PARAM_STR);
            $stmt->bindValue(3, $entity->getEmail(), \PDO::PARAM_STR);
            $stmt->bindValue(4, $entity->getHashPassword(), \PDO::PARAM_STR);
            $stmt->bindValue(5, $entity->getCreatedAt()->format('Y-m-d'), \PDO::PARAM_STR);
            $stmt->bindValue(6, $entity->getImgProfil(), \PDO::PARAM_STR);
            $stmt->bindValue(7, $entity->getGrant(), \PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch();
            if ($data) {
                $user = User::hydrate($data);
                $user->setId($this->connect->lastInsertId());
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return null;
        }
        return $user ?? null;
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
