<?php

declare(strict_types=1);

// Regression for S-01 and F-01 (no database service required).
// Run: php tests/security/reproduce_repository_leak.php
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Entity\User;
use App\Repository\UserRepository;

final class SimulatedDatabaseFailure extends PDO
{
    public function __construct() {}

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        throw new PDOException('internal SQL detail: private_database_name');
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        throw new PDOException('internal SQL detail: private_database_name');
    }
}

function verifyRepository(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$repository = new UserRepository(new SimulatedDatabaseFailure());
foreach (['find' => static fn () => $repository->find(1), 'findAll' => static fn () => $repository->findAll()] as $name => $call) {
    ob_start();
    $exception = null;
    try {
        $call();
    } catch (PDOException $caught) {
        $exception = $caught;
    } finally {
        $output = (string) ob_get_clean();
    }

    verifyRepository($exception instanceof PDOException, "$name must propagate PDO errors.");
    verifyRepository($output === '', "$name exposed a PDO exception in the HTTP output.");
}

$base = [
    'firstname' => 'Ada',
    'lastname' => 'Lovelace',
    'email' => 'ada@example.test',
    'hash_password' => 'hash',
    'created_at' => '2026-11-06 12:30:00',
];
$user = User::hydrate($base + ['updated_at' => '2026-11-07 12:00:00']);
verifyRepository($user->getCreatedAt() instanceof DateTimeImmutable, 'created_at must be a date object.');
verifyRepository($user->getCreatedAt()->format('Y-m-d H:i:s') === $base['created_at'], 'created_at changed.');
verifyRepository($user->getUpdatedAt() instanceof DateTimeImmutable, 'updated_at must be a date object.');
verifyRepository($user->getUpdatedAt()->format('Y-m-d H:i:s') === '2026-11-07 12:00:00', 'updated_at changed.');

$user = User::hydrate($base + ['updated_at' => null]);
verifyRepository($user->getUpdatedAt() === null, 'Nullable updated_at must remain null.');

try {
    User::hydrate($base + ['updated_at' => ['bad-data']]);
    throw new RuntimeException('Invalid updated_at type should have failed.');
} catch (UnexpectedValueException $expected) {
    // Malformed database records must not be silently accepted.
}

echo "Repository confidentiality and SQL date hydration: OK\n";
