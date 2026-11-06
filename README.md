# project_exemple — application Web (main)

Base PHP MVC pédagogique et utilisable pour un projet Web classique : sessions PHP, protection CSRF par middleware, DTO, services, repositories, injection de dépendances et autorisations par `grants`.


## Configuration des environnements

- **Développement** : copier `.env.example` vers `.env`, puis renseigner ses propres valeurs ; `APP_ENV=dev` par défaut.
- **Tests** : définir `APP_ENV=test` dans le processus pour charger `.env.test` (facultatif).
- **Production** : définir `APP_ENV=prod` et injecter les variables/secrets via le serveur ou le conteneur. Aucun fichier `.env.prod` ni `.env` n'est lu.

Les fichiers `.env` et `.env.*` sont ignorés par Git (sauf `.env.example`). Le fichier `.env` précédemment suivi par Git a été retiré du dépôt, sans effacer son historique : toute valeur sensible déjà publiée doit être renouvelée. Voir [guide des environnements](docs/environments.md).

```bash
php tests/environment_loader_test.php
```

## Organisation des branches

| Branche | Usage |
|---|---|
| `main` | **Web de référence**, sessions PHP, CSRF générique et `RouteGroup` |
| `variant/api-jwt` | API REST JSON, authentification Firebase JWT et grants |
| `archive/web-api-combined-2026-11-05` | Archive de l'ancien POC mixte Web + API |
| `variant/web-session-csrf` | Variante Web pédagogique, synchronisée sur le comportement de `main` |

Le code de la librairie `mithridatem/router` n'est pas modifié par cette organisation.

## Installation et exécution

```sh
composer install
# Copier .env.example vers .env, puis renseigner DATABASE_HOST, DATABASE_PORT, DATABASE_NAME,
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
- `src/Middleware/CsrfMiddleware.php` : protection générique des routes POST, avec un token indexé par chemin de soumission.

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

Pour ajouter un formulaire CSRF : protéger uniquement sa route POST avec `CsrfMiddleware` et générer le token avec son chemin public, sans modifier de registre. Les contrôleurs Login et Register utilisent `#[RouteGroup]` pour conserver les mêmes URL. Voir `docs/csrf-middleware.md` et `docs/route-groups-web.md`.
