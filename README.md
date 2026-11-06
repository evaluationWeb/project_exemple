# project_exemple — variante Web (sessions + CSRF)

Branche pédagogique **Web HTTP classique uniquement**. Authentification par session PHP, gestion des rôles avec `grants`, protection CSRF par middleware sur les formulaires HTML. Aucun endpoint JWT/API.

## Installation

```bash
git switch variant/web-session-csrf
composer install
php -S 127.0.0.1:8000 -t public
```

Configurez la base MySQL avec `db.sql` et vos variables `DATABASE_*` dans un fichier `.env` local.

## Architecture du point d'entrée

Le front controller `public/index.php` délègue désormais le démarrage à `App\Kernel\Application`. Le noyau Web reste volontairement léger :

- `src/Kernel/Application.php` orchestre le bootstrap, la configuration du conteneur, la création du routeur et le dispatch.
- `src/Kernel/WebBootstrap.php` charge `.env`, configure les en-têtes de sécurité et démarre la session PHP.
- `src/Kernel/RouterFactory.php` compose le routeur, l'autowiring, les grants de session et charge les attributs `#[Route]` / `#[RouteGroup]`.
- `src/Kernel/WebErrorHandler.php` traduit les exceptions en réponses HTML et codes HTTP adaptés, sans exposer les erreurs internes.

Ces classes sont propres **à l'application Web** : aucune logique de bootstrap n'est ajoutée aux librairies, et le profil API garde son `ApiBootstrap` / `ApiErrorHandler`.

L'authentification par session et le middleware CSRF basé sur le chemin public POST restent inchangés ; voir `docs/csrf-middleware.md`.

## Parcours

- `GET /login` et `POST /login` : connexion Web, session et CSRF.
- `GET /register` et `POST /register` : inscription et CSRF.
- Les `grants` sont lus depuis `$_SESSION['user']['grant']`.

## Tests

```bash
php tests/web_kernel_test.php
```

Ce test vérifie les routes groupées et publiques, la présence du middleware uniquement sur les POST, l'absence de routes API et les réponses HTML d'erreur.

Les autres tests existants restent applicables :


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

La branche `main` possède également un noyau Web ; cette PR aligne la variante Web sur cette organisation en conservant ses améliorations récentes.
