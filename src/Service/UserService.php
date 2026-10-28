<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterUserInput;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\PasswordHasher;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Validation\Validator;

final class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private Validator $validator,
        private PasswordHasher $passwordHasher,
    ) {}

    /** Ancien point d'entrée maintenu pour les appels existants. */
    public function addUser(Request $request): array
    {
        return $this->register(RegisterUserInput::fromArray([
            'firstname' => $request->post('firstname'),
            'lastname' => $request->post('lastname'),
            'email' => $request->post('email'),
            'password' => $request->post('password'),
            'confirm_password' => $request->post('confirm_password'),
        ]));
    }

    /** Cas d'utilisation indépendant de HTTP. */
    public function register(RegisterUserInput $input): array
    {
        $errors = $this->validateRegistration($input);
        if ($errors !== []) {
            return $errors;
        }

        // Les champs requis sont non nuls une fois les contraintes validées.
        $email = $input->email;
        if ($this->userRepository->findByEmail($email) !== null) {
            return ['errors' => 'Le compte ' . $email . ' existe déjà'];
        }

        $user = $this->createUser($input);
        $this->userRepository->create($user);

        return ['success' => 'Le compte ' . $email . ' a été ajouté avec succès'];
    }

    private function validateRegistration(RegisterUserInput $input): array
    {
        $errors = [];
        $violations = $this->validator->validate($input);
        if ($violations !== []) {
            $errors['validation'] = $violations;
        }

        if ($input->password !== $input->confirmPassword) {
            $errors['errors'] = 'Les mots de passe ne sont pas identiques';
        }

        return $errors;
    }

    private function createUser(RegisterUserInput $input): User
    {
        $user = new User(
            $input->firstname,
            $input->lastname,
            $input->email,
            $this->passwordHasher->hash($input->password),
        );
        $user->setGrant('ROLE_USER');

        return $user;
    }
}
