<?php

declare(strict_types=1);

// Exécution : php tests/csrf_token_manager_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Security\CsrfTokenManager;

function assertCsrf(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

session_start();

$csrf = new CsrfTokenManager();
$login = $csrf->generate('login');
$register = $csrf->generate('register');

assertCsrf(strlen($login) === 64, 'Longueur du token inattendue');
assertCsrf($login === $csrf->generate('login'), 'Le token doit être stable pendant la session');
assertCsrf($login !== $register, 'Chaque formulaire doit avoir son propre token');
assertCsrf($csrf->isValid('login', $login), 'Token valide rejeté');
assertCsrf(!$csrf->isValid('login', $register), 'Token d’un autre formulaire accepté');
assertCsrf(!$csrf->isValid('login', null), 'Token absent accepté');
assertCsrf(!$csrf->isValid('login', ''), 'Token vide accepté');
assertCsrf(!$csrf->isValid('login', ['unexpected']), 'Tableau HTTP accepté');
assertCsrf(!$csrf->isValid('login', 'wrong'), 'Token invalide accepté');
assertCsrf(!$csrf->isValid('unknown', $login), 'Identifiant de formulaire inconnu accepté');

session_write_close();

try {
    $csrf->generate('login');
    throw new RuntimeException('La session fermée aurait dû être rejetée');
} catch (LogicException $expected) {
    // Une session active est requise.
}

echo "CsrfTokenManager : OK\n";
