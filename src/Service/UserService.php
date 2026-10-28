<?php

namespace App\Service;

use App\Entity\Entity;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Tools;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Validation\Validator;

final class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private Validator $validator
    ) {}

    public function addUser(Request $request): array
    {
        //Récupération des données
        $recup = [
            "firstname" => $request->post("firstname") ?? null,
            "lastname" => $request->post("lastname") ?? null,
            "email" => $request->post("email") ?? null,
            "hash_password" => $request->post("password") ?? null,
            "confirm_password" => $request->post("confirm_password") ?? null,
        ];
        //Nettoyage des entrées utilisateur
        Tools::sanitizeArray($recup);

        //Hydratation en Objet User
        $user = User::hydrate($recup);

        //Récupération et tests des erreurs de validations
        $validation_errrors = $this->validator->validate($user);
        if (!empty($validation_errrors)) {
            $data["validation"] = $validation_errrors;
        }

        //Test si les mots de passe sont différents
        if ($user->getHashPassword() != $request->post("confirm_password")) {
            $data["errors"] = "Les mots de passes ne sont pas identiques";
        }
        
        //Test si le compte existe déja
        if (empty($data) && null !== $this->userRepository->findByEmail($user->getEmail())) {
            $data["errors"] = "Le compte " . $user->getEmail() . " existe déja";
        }

        //Assignation des attributs et insert en BDD
        if (empty($data)) {
            $user->hashPassword();
            $user->setGrant("ROLE_USER");
            $this->userRepository->create($user);
            $data["success"] = "Le compte " . $user->getEmail() . " a été ajouté avec succés";
        }
        return $data ?? [];
    }
}
