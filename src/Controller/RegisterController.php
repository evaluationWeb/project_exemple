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
        $data = [];

        if ($this->isFormSubmitted($request)) {
            if (!$this->csrf->isValid('register', $request->post('csrf_token'))) {
                $data['errors'] = 'Token CSRF invalide';
            } else {
                $input = RegisterUserInput::fromArray([
                    'firstname' => $request->post('firstname'),
                    'lastname' => $request->post('lastname'),
                    'email' => $request->post('email'),
                    'password' => $request->post('password'),
                    'confirm_password' => $request->post('confirm_password'),
                ]);

                $data = $this->userService->register($input);
            }
        }

        // Garantit un token présent lors du GET et après un échec de formulaire.
        $data['csrf_token'] = $this->csrf->generate('register');

        return $this->render('register', 'Inscription', $data);
    }
}
