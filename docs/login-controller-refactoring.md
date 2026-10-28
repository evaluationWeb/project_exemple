# Refactorisation LoginController : responsabilité HTTP

## Nouvelle répartition

- `LoginController` : routes GET/POST, contrôle CSRF, conversion des champs en `LoginInput`, affichage d'erreurs et redirection HTTP.
- `LoginService` : validation du DTO, recherche de l'utilisateur et vérification du mot de passe. Il ne manipule pas la session.
- `UserSessionManager` : régénère l'identifiant de session et stocke seulement l'id, l'email et les rôles du compte (tableau de rôles).
- `LoginInput` : contraintes des champs entrants.

## Parcours

```text
GET /login -> LoginController::showLogin() -> template_login.php
POST /login -> LoginController::login()
            -> contrôle CSRF
            -> LoginInput::fromArray(...)
            -> LoginService::login(...)
             si erreur -> rendu de la vue
             si succès -> UserSessionManager::login(User)
                       -> redirection / (303)
```

`UserSessionManager::logout()` est disponible pour un futur cas d'utilisation dédié, sans ajouter de route dans cette PR.

## Vérification locale

```sh
composer install
php tests/login_input_test.php
php tests/user_session_manager_test.php
php -S 127.0.0.1:8000 -t public
```

Vérifier GET/POST sur `/login`, CSRF absent, identifiants incorrects, session après connexion et redirection. Les routes séparées GET et POST nécessitent une vérification d'intégration avec la version installée du routeur.

Les tests ne sont pas exécutés automatiquement par cette PR.
