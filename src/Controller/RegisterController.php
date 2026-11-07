<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RegisterUserInput;
use App\Middleware\CsrfMiddleware;
use App\Security\CsrfTokenManager;
use App\Service\UserService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Attribute\RouteGroup;
use Mithridatem\Routing\Http\ParameterCollectionRequestInterface;

#[RouteGroup(prefix: '/register')]
final class RegisterController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private CsrfTokenManager $csrf,
    ) {}

    #[Route(path: '/', method: 'GET', grants: ['IS_GUEST'])]
    #[Route(path: '/', method: 'POST', grants: ['IS_GUEST'], middleware: [CsrfMiddleware::class])]
    public function register(ParameterCollectionRequestInterface $request): mixed
    {
        $result = [];

        if ($this->isFormSubmitted($request)) {
            $input = RegisterUserInput::fromArray($request->allPost());
            $result = $this->userService->register($input);
        }

        return $this->renderRegisterForm($result);
    }

    private function renderRegisterForm(array $data = []): mixed
    {
        return $this->render('register', 'Inscription', [
            ...$data,
            'csrf_token' => $this->csrf->generate('/register'),
        ]);
    }
}
