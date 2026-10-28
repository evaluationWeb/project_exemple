<?php

declare(strict_types=1);

// php tests/user_session_manager_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Entity\User;
use App\Security\UserSessionManager;

function verifySession(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

session_start();
$manager = new UserSessionManager();
$user = new User('Ada', 'Lovelace', 'ada@example.test', 'password-hash');
$user->setId(42)->setGrant('ROLE_USER, ROLE_ADMIN, ,ROLE_EDITOR');
$manager->login($user);
verifySession($_SESSION['user']['id'] === 42, 'Identifiant manquant');
verifySession($_SESSION['user']['email'] === 'ada@example.test', 'Email manquant');
verifySession($_SESSION['user']['grant'] === ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_EDITOR'], 'Rôles mal convertis');
verifySession(!isset($_SESSION['user']['hash_password']), 'Le hash ne doit pas figurer en session');
$manager->logout();
verifySession(!isset($_SESSION['user']), 'La déconnexion doit supprimer l’utilisateur');
session_write_close();
echo "UserSessionManager : OK\n";
