<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Service\UserService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Http\RequestInterface;

final class RegisterController extends AbstractController
{
    public function __construct(private UserService $userService) {}

    #[Route(path: '/register', method: ['GET', 'POST'], grants: [])]
    public function register(RequestInterface $request): mixed
    {
        if ($this->isFormSubmitted($request)) {
            $data = $this->userService->addUser($request);
        }
        return $this->render('register', 'Inscription', $data ?? []);
    }
}
