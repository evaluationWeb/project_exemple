# project_exemple — application Web (main)

Base PHP MVC pédagogique et utilisable pour un projet Web classique : sessions PHP, protection CSRF par middleware, DTO, services, repositories, injection de dépendances et autorisations par `grants`.

## Organisation des branches

| Branche | Usage |
|---|---|
| `main` | **Web uniquement**, sessions PHP et CSRF (cette version après fusion de la PR) |
| `variant/api-jwt` | API REST JSON, authentification Firebase JWT et grants |
| `archive/web-api-combined-2026-11-05` | Archive de l'ancien POC mixte Web + API |
| `variant/web-session-csrf` | Variante Web antérieure, conservée à titre de référence |

Le code de la librairie `mithridatem/router` n'est pas modifié par cette organisation.

## Installation et exécution

```sh
composer install
# Renseigner votre .env avec DATABASE_HOST, DATABASE_PORT, DATABASE_NAME,
# DATABASE_USERNAME, DATABASE_PASSWORD et UPLOAD_DIRECTORY
# Importer db.sql dans MySQL/MariaDB
php -S 127.0.0.1:8000 -t public
```

Endpoints Web : `/`, `/login` (GET/POST), `/register` (GET/POST).

## Un front controller simple

```php
require dirname(__DIR__) . '/vendor/autoload.php';
(new App\Kernel\Application(dirname(__DIR__)))->run();
```

- `src/Kernel/Application.php` : coordination uniquement (bootstrap, conteneur, routeur et dispatch).
- `src/Kernel/WebBootstrap.php` : variables d'environnement, en-têtes de sécurité et session PHP.
- `src/Kernel/RouterFactory.php` : résolution des contrôleurs, middlewares, grants de session et chargement des routes Attributes.
- `src/Kernel/WebErrorHandler.php` : codes HTTP et sortie HTML échappée, sans exposer les erreurs internes.
- `config/service.php` : déclaration des services (PDO, configuration de l'upload) via `Container::load()`.
- `src/Middleware/CsrfMiddleware.php` : contrôle des tokens sur les POST de connexion/inscription.

Ces objets restent **dans l'application** : le conteneur d'autowiring ne connaît ni HTTP, ni routing, ni session. Aucun nouveau framework ni API publique de librairie n'est introduit.

## Tests

```sh
php tests/web_kernel_test.php
php tests/csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/csrf_route_configuration_test.php
php tests/auth_form_render_test.php
php tests/form_submission_test.php
php tests/request_dto_mapping_test.php
php tests/login_input_test.php
php tests/register_user_input_test.php
php tests/user_session_manager_test.php
```

La CI exécute les tests statiques et fonctionnels du bootstrap et des formulaires. Vérifier ensuite manuellement le parcours HTML contre une base SQL locale : inscription, authentification, grants et protection CSRF.

Voir aussi `docs/csrf-middleware.md`.
