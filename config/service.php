<?php

declare(strict_types=1);

use App\Database\Mysql;
use App\Service\UploadFileService;
use Mithridatem\Autowiring\Container;

return static function (Container $container): void {
    // Connexion créée au premier besoin, puis partagée.
    $container->factory(
        PDO::class,
        static fn(): PDO => Mysql::connectBdd()
    );
    // configuration du répertoire d'upload
    $container->configure(UploadFileService::class, [
        'uploadDirectory' => $_ENV["UPLOAD_DIRECTORY"]
    ]);
    // Découvrir les implémentations des interfaces de repositories.
    /* $container->discover(
        dirname(__DIR__) . '/src/Repository',
        'App\\Repository'
    ); */
};
