<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LoginInput;
use App\Middleware\CsrfMiddleware;
use App\Security\CsrfTokenManager;
use App\Security\UserSessionManager;
use App\Service\LoginService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Attribute\RouteGroup;
use Mithridatem\Routing\Http\ParameterCollectionRequestInterface;

#[RouteGroup(prefix: '/login')]
final class LoginController extends AbstractController
{
    public function __construct(
        private LoginService $loginService,
        private UserSessionManager $session,
        private CsrfTokenManager $csrf,
    ) {}

    #[Route(path: '/', method: 'GET', grants: ['IS_GUEST'])]
    public function showLogin(): mixed
    {
        return $this->renderLoginForm();
    }

    #[Route(path: '/', method: 'POST', grants: ['IS_GUEST'], middleware: [CsrfMiddleware::class])]
    public function login(ParameterCollectionRequestInterface $request): mixed
    {
        $input = LoginInput::fromArray($request->allPost());
        $result = $this->loginService->login($input);

        if (isset($result['user'])) {
            $this->session->login($result['user']);
            $this->redirect('/');
        }

        return $this->renderLoginForm($result);
    }

    private function renderLoginForm(array $data = []): mixed
    {
        return $this->render('login', 'Connexion', [
            ...$data,
            'csrf_token' => $this->csrf->generate('/login'),
        ]);
    }
}
