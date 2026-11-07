<?php

declare(strict_types=1);

namespace App\Security\Jwt;

use App\Entity\User;
use App\Security\Authentication\AuthenticatedUser;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

final class JwtTokenManager
{
    public function __construct(
        private string $secret,
        private string $issuer,
        private string $audience,
        private int $ttl
    ) {
        if (strlen($secret) < 32 || $issuer === '' || $audience === '') {
            throw new \LogicException('JWT configuration missing: define a random JWT_SECRET (32+ bytes), JWT_ISSUER and JWT_AUDIENCE.');
        }

        if ($ttl <= 0) {
            throw new \InvalidArgumentException('JWT_TTL_SECONDS must be a positive integer.');
        }
    }

    public function issue(User $user): string
    {
        $id = $user->getId();
        $email = $user->getEmail();
        if ($id === null || $id <= 0 || !is_string($email) || $email === '') {
            throw new \LogicException('Cannot issue a JWT without a persistent user identity.');
        }

        $now = time();
        return JWT::encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => (string) $id,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'jti' => bin2hex(random_bytes(16)),
            'email' => $email,
            'roles' => self::parseRoles($user->getGrant()),
        ], $this->secret, 'HS256');
    }

    public function authenticate(string $token): AuthenticatedUser
    {
        if ($token === '') {
            throw new InvalidJwtException('Invalid bearer token.');
        }

        try {
            $claims = (array) JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (Throwable $e) {
            throw new InvalidJwtException('Invalid bearer token.', 0, $e);
        }

        $now = time();
        if (
            ($claims['iss'] ?? null) !== $this->issuer
            || ($claims['aud'] ?? null) !== $this->audience
            || !isset($claims['sub']) || !is_string($claims['sub'])
            || !ctype_digit($claims['sub']) || (int) $claims['sub'] <= 0
            || !isset($claims['email']) || !is_string($claims['email']) || $claims['email'] === ''
            || !isset($claims['jti']) || !is_string($claims['jti']) || $claims['jti'] === ''
            || !isset($claims['iat'], $claims['nbf'], $claims['exp'])
            || !is_int($claims['iat']) || !is_int($claims['nbf']) || !is_int($claims['exp'])
            || $claims['iat'] > $now || $claims['nbf'] > $now
            || $claims['exp'] <= $now || $claims['exp'] <= $claims['iat']
            || $claims['exp'] - $claims['iat'] > $this->ttl
            || !isset($claims['roles']) || !is_array($claims['roles'])
        ) {
            throw new InvalidJwtException('Invalid bearer token claims.');
        }

        $roles = $claims['roles'];
        foreach ($roles as $role) {
            if (!is_string($role) || $role === '' || strlen($role) > 100) {
                throw new InvalidJwtException('Invalid bearer token roles.');
            }
        }

        return new AuthenticatedUser((int) $claims['sub'], $claims['email'], array_values(array_unique($roles)));
    }

    public function expiresIn(): int
    {
        return $this->ttl;
    }

    /** @return list<string> */
    private static function parseRoles(?string $value): array
    {
        return array_values(array_unique(array_filter(
            array_map('trim', explode(',', $value ?? '')),
            static fn (string $role): bool => $role !== ''
        )));
    }
}
