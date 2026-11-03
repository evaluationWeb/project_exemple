<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LoginInput;
use App\Security\CsrfTokenManager;
use App\Security\UserSessionManager;
use App\Service\LoginService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Http\ParameterCollectionRequestInterface;

final class LoginController extends AbstractController
{
    public function __construct(
        private LoginService $loginService,
        private UserSessionManager $session,
        private CsrfTokenManager $csrf,
    ) {}

    #[Route(path: '/login', method: 'GET', grants: [])]
    public function showLogin(): mixed
    {
        return $this->renderLoginForm();
    }

    #[Route(path: '/login', method: 'POST', grants: [])]
    public function login(ParameterCollectionRequestInterface $request): mixed
    {
        $result = [];

        $isCsrfValid = $this->csrf->isValid(
            'login',
            $request->post('csrf_token')
        );

        if (!$isCsrfValid) {
            $result = ['errors' => 'Token CSRF invalide'];
        }

        if ($isCsrfValid) {
            $input = LoginInput::fromArray($request->allPost());

            $result = $this->loginService->login($input);

            if (isset($result['user'])) {
                $this->session->login($result['user']);
                $this->redirect('/');
            }
        }

        return $this->renderLoginForm($result);
    }

    private function renderLoginForm(array $data = []): mixed
    {
        return $this->render('login', 'Connexion', [
            ...$data,
            'csrf_token' => $this->csrf->generate('login'),
        ]);
    }
}
