<?php

declare(strict_types=1);

// Manual audit reproducer (not in CI until the issues are corrected).
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

$repository = new UserRepository(new SimulatedDatabaseFailure());
foreach (['find' => fn () => $repository->find(1), 'findAll' => fn () => $repository->findAll()] as $name => $call) {
    ob_start();
    try {
        $call();
        $output = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }

    if (!str_contains($output, 'private_database_name')) {
        throw new RuntimeException("Expected the current repository error leak in $name; review this reproducer if fixed.");
    }
    echo "$name: SQL exception message is exposed in output (confirmed)\n";
}

try {
    User::hydrate([
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'email' => 'ada@example.test',
        'hash_password' => 'hash',
        'updated_at' => '2026-11-07 12:00:00',
    ]);
    throw new RuntimeException('Expected TypeError for the raw SQL timestamp.');
} catch (TypeError $expected) {
    echo "User::hydrate: non-null SQL updated_at string triggers TypeError (confirmed)\n";
}
