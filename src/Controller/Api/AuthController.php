<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\LoginInput;
use App\Security\Jwt\JwtTokenManager;
use App\Service\LoginService;
use JsonException;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Attribute\RouteGroup;
use Mithridatem\Routing\Http\RequestInterface;

#[RouteGroup(prefix: '/api')]
final class AuthController
{
    public function __construct(private LoginService $login, private JwtTokenManager $tokens) {}

    #[Route(path: '/login', method: 'POST')]
    public function login(RequestInterface $request): void
    {
        try {
            $body = json_decode($request->getContent(), true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->respond(['error' => 'Invalid JSON body'], 400);
            return;
        }

        if (!is_array($body) || array_is_list($body)) {
            $this->respond(['error' => 'Expected a JSON object'], 400);
            return;
        }

        $result = $this->login->login(LoginInput::fromArray($body));
        if (!isset($result['user'])) {
            $this->respond(['error' => 'Invalid credentials or input'], 401);
            return;
        }

        $this->respond([
            'access_token' => $this->tokens->issue($result['user']),
            'token_type' => 'Bearer',
            'expires_in' => $this->tokens->expiresIn(),
        ], 200);
    }

    private function respond(array $data, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }
}
