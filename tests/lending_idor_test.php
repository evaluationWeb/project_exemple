<?php

declare(strict_types=1);

// php tests/lending_idor_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Repository\LendingRepository;
use App\Service\Exception\LendingNotFoundException;
use App\Service\LendingService;

function assertIdor(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectNotFound(callable $operation): void
{
    try {
        $operation();
    } catch (LendingNotFoundException) {
        return;
    }

    throw new RuntimeException('Une ressource étrangère ou absente doit être refusée.');
}

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo "Lending IDOR: SKIP (extension pdo_sqlite manquante)\n";
    exit(0);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE lending (
    id_lending INTEGER PRIMARY KEY,
    lending_at TEXT NOT NULL,
    status INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL
)');
$pdo->exec("INSERT INTO lending VALUES
    (10, '2026-01-01', 1, 1, 100),
    (20, '2026-01-02', 1, 2, 200),
    (30, '2026-01-03', 1, 1, 300)");

$service = new LendingService(new LendingRepository($pdo));

// Propriétaire : consultation et liste autorisées.
assertIdor((int) $service->show(10, 1)['user_id'] === 1, 'Lecture propriétaire échouée');
assertIdor(count($service->listMine(1)) === 2, 'Liste propriétaire incorrecte');
assertIdor(count($service->listMine(2)) === 1, 'Liste second utilisateur incorrecte');
assertIdor($service->listMine(3) === [], 'Un nouvel utilisateur ne doit rien voir');

// IDOR : connaître l'identifiant ne permet ni lecture ni modification.
expectNotFound(fn() => $service->show(20, 1));
expectNotFound(fn() => $service->show(999, 1));
expectNotFound(fn() => $service->show(10, -1));
expectNotFound(fn() => $service->returnBook(20, 1));
assertIdor((int) $service->show(20, 2)['status'] === 1, 'Un emprunt tiers a été modifié');

// Le propriétaire peut rendre son emprunt, mais pas une seconde fois.
$service->returnBook(10, 1);
assertIdor((int) $service->show(10, 1)['status'] === 0, 'Retour propriétaire non enregistré');
expectNotFound(fn() => $service->returnBook(10, 1));
expectNotFound(fn() => $service->returnBook(999, 1));

echo "Lending IDOR: OK\n";
