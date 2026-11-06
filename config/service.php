<?php

declare(strict_types=1);

use App\Database\Mysql;
use App\Security\Jwt\JwtTokenManager;
use Mithridatem\Autowiring\Container;

return static function (Container $container): void {
    $container->configure(JwtTokenManager::class, [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'issuer' => $_ENV['JWT_ISSUER'] ?? 'project-exemple',
        'audience' => $_ENV['JWT_AUDIENCE'] ?? 'project-exemple-api',
        'ttl' => $_ENV['JWT_TTL_SECONDS'] ?? 900,
    ]);

    // Connexion créée au premier besoin, puis partagée.
    $container->factory(
        PDO::class,
        static fn(): PDO => Mysql::connectBdd()
    );

    // Découvrir les implémentations des interfaces de repositories.
    /* $container->discover(
        dirname(__DIR__) . '/src/Repository',
        'App\\Repository'
    ); */
};
