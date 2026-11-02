<?php

declare(strict_types=1);

// Lancer : php tests/csrf_token_manager_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Security\CsrfTokenManager;

function expectCsrf(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

session_start();
$csrf = new CsrfTokenManager();
$login = $csrf->generate('login');
$register = $csrf->generate('register');

expectCsrf(strlen($login) === 64, 'Longueur du token incorrecte');
expectCsrf($csrf->generate('login') === $login, 'Le token a changé');
expectCsrf($login !== $register, 'Les deux formulaires partagent un token');
expectCsrf($csrf->isValid('login', $login), 'Token valide refusé');
expectCsrf(!$csrf->isValid('login', $register), 'Token d’un autre formulaire accepté');
expectCsrf(!$csrf->isValid('register', $login), 'Token d’un autre formulaire accepté');
expectCsrf(!$csrf->isValid('login', null), 'Token absent accepté');
expectCsrf(!$csrf->isValid('login', ''), 'Token vide accepté');
expectCsrf(!$csrf->isValid('login', ['unexpected']), 'Un tableau HTTP doit être refusé');
expectCsrf(!$csrf->isValid('login', 123), 'Un entier HTTP doit être refusé');
expectCsrf(!$csrf->isValid('login', 'wrong'), 'Token incorrect accepté');
expectCsrf(!$csrf->isValid('unknown', $login), 'Token de formulaire inexistant accepté');

session_write_close();

try {
    $csrf->generate('login');
} catch (LogicException $expected) {
    echo "CsrfTokenManager : OK\n";
    exit(0);
}

throw new RuntimeException('Une session fermée aurait dû être rejetée');
