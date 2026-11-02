<?php

declare(strict_types=1);

namespace App\Security;

final class CsrfTokenManager
{
    /**
     * Méthode qui génére un token CSRF
     * @param string $formId id du formulaire
     * @return string token csrf
     */
    public function generate(string $formId): string
    {
        $this->ensureSession();

        if (!isset($_SESSION['csrf_tokens'][$formId])) {
            $_SESSION['csrf_tokens'][$formId] =
                bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_tokens'][$formId];
    }

    /**
     * Méthode qui teste si le Token CSRF est valide
     * @param string $formId id du formulaire
     * @param mixed $token valeur reçue de la requête HTTP
     * @return bool true si valide ou false
     */
    public function isValid(
        string $formId,
        mixed $token
    ): bool {
        $this->ensureSession();

        $storedToken =
            $_SESSION['csrf_tokens'][$formId] ?? null;

        if (
            !is_string($token)
            || !is_string($storedToken)
            || $token === ''
            || $storedToken === ''
        ) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Méthode qui vérifie si la session PHP est active
     *  @return void 
     * @throws \LogicException exception levée si pas de session PHP active
     */
    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \LogicException(
                'Une session PHP active est nécessaire.'
            );
        }
    }
}
