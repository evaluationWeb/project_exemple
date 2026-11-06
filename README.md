# project_exemple — variante Web (sessions + CSRF)

Branche pédagogique **Web HTTP classique uniquement**. Authentification par session PHP, gestion des rôles avec `grants`, protection CSRF par middleware sur les formulaires HTML. Aucun endpoint JWT/API.

## Installation

```bash
git switch variant/web-session-csrf
composer install
php -S 127.0.0.1:8000 -t public
```

Configurez la base MySQL avec `db.sql` et vos variables `DATABASE_*` dans un fichier `.env` local.

## Parcours

- `GET /login` et `POST /login` : connexion Web, session et CSRF.
- `GET /register` et `POST /register` : inscription et CSRF.
- Les `grants` sont lus depuis `$_SESSION['user']['grant']`.

## Tests

```bash
php tests/csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/csrf_route_configuration_test.php
php tests/form_submission_test.php
php tests/auth_form_render_test.php
php tests/request_dto_mapping_test.php
php tests/login_input_test.php
php tests/register_user_input_test.php
php tests/user_session_manager_test.php
```

Pour protéger un nouveau formulaire, appliquez `CsrfMiddleware` uniquement à la route POST et générez le token avec le chemin de soumission correspondant. Aucun registre de formulaires n'est à maintenir. Consultez `docs/csrf-middleware.md` pour les exemples et les limites.

La branche `main` conserve l'exemple complet incluant également l'API JWT.
