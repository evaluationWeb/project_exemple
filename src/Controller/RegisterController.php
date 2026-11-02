<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RegisterUserInput;
use App\Security\CsrfTokenManager;
use App\Service\UserService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Http\RequestInterface;

final class RegisterController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private CsrfTokenManager $csrf,
    ) {}

    #[Route(path: '/register', method: ['GET', 'POST'], grants: [])]
    public function register(RequestInterface $request): mixed
    {
        if (!$this->isFormSubmitted($request)) {
            return $this->renderRegisterForm();
        }

        if (!$this->csrf->isValid('register', $request->post('csrf_token'))) {
            return $this->renderRegisterForm([
                'errors' => 'Token CSRF invalide',
            ]);
        }

        $input = RegisterUserInput::fromArray([
            'firstname' => $request->post('firstname'),
            'lastname' => $request->post('lastname'),
            'email' => $request->post('email'),
            'password' => $request->post('password'),
            'confirm_password' => $request->post('confirm_password'),
        ]);

        $result = $this->userService->register($input);

        return $this->renderRegisterForm($result);
    }

    private function renderRegisterForm(array $data = []): mixed
    {
        return $this->render('register', 'Inscription', [
            ...$data,
            'csrf_token' => $this->csrf->generate('register'),
        ]);
    }
}
