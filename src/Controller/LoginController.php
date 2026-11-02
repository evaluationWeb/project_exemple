<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LoginInput;
use App\Security\CsrfTokenManager;
use App\Security\UserSessionManager;
use App\Service\LoginService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Http\RequestInterface;

final class LoginController extends AbstractController
{
    public function __construct(
        private LoginService $loginService,
        private UserSessionManager $session,
        private CsrfTokenManager $csrf
    ) {}

    #[Route(path: '/login', method: 'GET', grants: [])]
    public function showLogin(): mixed
    {
        return $this->render('login', 'Connexion', [
            'csrf_token' => $this->csrf->generate('login')
        ]);
    }

    #[Route(path: '/login', method: 'POST', grants: [])]
    public function login(RequestInterface $request): mixed
    {
        if (!$this->csrf->isValid(
            'login',
            $request->post('csrf_token')
        )) {
            return $this->render('login', 'Connexion', [
                'errors' => 'Token CSRF invalide',
                'csrf_token' => $this->csrf->generate('login'),
            ]);
        }

        $result = $this->loginService->login(LoginInput::fromArray([
            'email' => $request->post('email'),
            'password' => $request->post('password'),
        ]));

        if (!isset($result['user'])) {
            return $this->render('login', 'Connexion', [
                ...$result,
                'csrf_token' => $this->csrf->generate('login'),
            ]);
        }

        $this->session->login($result['user']);
        $this->redirect('/');
    }
}
