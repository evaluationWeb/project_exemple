<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LoginInput;
use App\Service\LoginService;
use Mithridatem\Routing\Attribute\Route;
use Mithridatem\Routing\Http\RequestInterface;

final class LoginController extends AbstractController
{
    public function __construct(private LoginService $loginService) {}

    #[Route(path: '/login', method: ['GET', 'POST'], grants: [])]
    public function login(RequestInterface $request): mixed
    {
        $data = [];
        if ($this->isFormSubmitted($request)) {
            if (!$this->isCsrfTokenValid(['csrf_token' => $request->post('csrf_token')])) {
                $data['errors'] = 'Formulaire expiré. Veuillez réessayer.';
                return $this->render('login', 'Connexion', $data);
            }
            // HTTP reste dans le contrôleur ; la logique métier reçoit un DTO.
            $input = LoginInput::fromArray([
                'email' => $request->post('email'),
                'password' => $request->post('password'),
            ]);
            $result = $this->loginService->login($input);

            if (isset($result['user'])) {
                $user = $result['user'];
                session_regenerate_id(true);
                // Ne jamais stocker de hash de mot de passe en session.
                $_SESSION['user'] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'grant' => array_values(array_filter(
                        array_map('trim', explode(',', $user->getGrant() ?? ''))
                    )),
                ];
                header('Location: /', true, 303);
                exit;
            }

            $data = $result;
        }

        return $this->render('login', 'Connexion', $data);
    }
}
