<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Middleware\JwtMiddleware;
use App\Security\Authentication\AuthenticationContext;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: '/api', grants: ['ROLE_USER'], middleware: [JwtMiddleware::class])]
final class ProfileController
{
    public function __construct(private AuthenticationContext $context) {}

    #[Route(path: '/profile', method: 'GET')]
    public function profile(): void
    {
        $user = $this->context->getUser();
        if ($user === null) {
            throw new \LogicException('Authenticated context missing after JWT middleware.');
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'roles' => $user->grants,
        ], JSON_THROW_ON_ERROR);
    }
}
