# Cas d'utilisation : connexion (Login)

Le cas d'utilisation suit le même découpage que l'inscription :

```text
POST /login
  LoginController             (HTTP, CSRF, session)
    -> LoginInput             (DTO et contraintes)
    -> LoginService::login()  (validation et authentification)
       -> Validator
       -> UserRepository::findByEmail()
       -> PasswordHasher::verify()
```

## Rôles

- **LoginInput** porte les contraintes Email et NotBlank. Il normalise l'email mais ne modifie jamais le mot de passe.
- **LoginService** ne dépend ni de HTTP, ni de `$_SESSION`. Il retourne `['validation' => ...]` si les données sont invalides, `['errors' => ...]` en cas d'identifiants incorrects, ou `['user' => $user]` après authentification.
- **LoginController** gère le CSRF, la création du DTO, la session et la redirection HTTP 303.
- **UserRepository** hydrate l'utilisateur avec son identifiant. Une erreur SQL n'est pas assimilée à un compte inconnu.
- **PasswordHasher** est partagé avec l'inscription.

## Tester

```bash
composer install
php tests/login_input_test.php
php -S 127.0.0.1:8000 -t public
```

Tester ensuite `GET /login`, un mot de passe incorrect, un compte inconnu, des champs absents, un jeton CSRF absent et une connexion réussie. La connexion réussie doit renouveler l'ID de session et ne mémoriser que `id`, `email` et `grant` (tableau). Aucune chaîne de hash ne doit apparaître en session.

**À prévoir avant production :** limitation de débit/tentatives, HTTPS, politique de cookies adaptée, gestion centralisée des exceptions techniques et tests d'intégration HTTP+MySQL. Une route de déconnexion dédiée est un cas d'utilisation distinct.
