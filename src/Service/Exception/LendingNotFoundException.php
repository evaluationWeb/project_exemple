<?php

declare(strict_types=1);

namespace App\Service\Exception;

/** Ne révèle pas si l'emprunt appartient à un autre utilisateur. */
final class LendingNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Emprunt introuvable.');
    }
}
