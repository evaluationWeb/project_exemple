<?php

namespace App\Entity;

use App\Entity\Entity;
use Mithridatem\Validation\Attributes\Email;
use Mithridatem\Validation\Attributes\Length;
use Mithridatem\Validation\Attributes\NotBlank;

class User implements Entity
{
    private ?int $id = null;
    #[NotBlank]
    #[Length(min: 2, max: 50)]
    private ?string $firstname;
    #[NotBlank]
    #[Length(min: 2, max: 50)]
    private ?string $lastname;
    #[NotBlank]
    #[Email]
    private ?string $email;
    #[NotBlank]
    #[Length(min: 4, max: 50)]
    private ?string $hashPassword;
    private ?string $grant = null;
    private ?string $imgProfil = null;
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        ?string $firstname,
        ?string $lastname,
        ?string $email,
        ?string $hashPassword
    ) {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->hashPassword = $hashPassword;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getHashPassword(): ?string
    {
        return $this->hashPassword;
    }

    public function setHashPassword(?string $hashPassword): self
    {
        $this->hashPassword = $hashPassword;
        return $this;
    }

    public function getGrant(): ?string
    {
        return $this->grant;
    }

    public function setGrant(?string $grant): self
    {
        $this->grant = $grant;
        return $this;
    }

    public function getImgProfil(): ?string
    {
        return $this->imgProfil;
    }

    public function setImgProfil(?string $imgProfil): self
    {
        $this->imgProfil = $imgProfil;
        return $this;
    }


    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function hashPassword(): void
    {
        $this->hashPassword = password_hash(
            $this->hashPassword,
            PASSWORD_DEFAULT
        );
    }

    public function passwordVerify(string $clearPassword): bool
    {
        return password_verify(
            $clearPassword,
            $this->hashPassword
        );
    }

    public static function hydrate(array $user): User
    {
        $hydrateUser = new User(
            $user['firstname'] ?? null,
            $user['lastname'] ?? null,
            $user['email'] ?? null,
            $user['hash_password'] ?? null
        );

        // PDO returns DATETIME columns as strings. Convert them at the
        // persistence boundary instead of passing strings to typed setters.
        if (isset($user['created_at'])) {
            $hydrateUser->setCreatedAt(
                self::hydrateDate($user['created_at'])
            );
        }

        $hydrateUser->setUpdatedAt(
            self::hydrateDate($user['updated_at'] ?? null)
        );
        $hydrateUser->setGrant($user['grant'] ?? null);
        $hydrateUser->setImgProfil($user['img_profil'] ?? null);

        return $hydrateUser;
    }

    private static function hydrateDate(mixed $date): ?\DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        if (!is_string($date)) {
            throw new \UnexpectedValueException('Invalid database date type.');
        }

        try {
            return new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            throw new \UnexpectedValueException('Invalid database date format.', 0, $e);
        }
    }
}
