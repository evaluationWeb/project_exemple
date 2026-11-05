# project_exemple — variante API JWT

Branche pédagogique **API REST uniquement** : aucun rendu de template HTML, aucune authentification par session PHP et aucune protection CSRF basée sur cookies.

## Installation

```bash
git switch variant/api-jwt
composer install
php -S 127.0.0.1:8000 -t public
```

Configurer `.env` pour MySQL (script `db.sql`) et les paramètres suivants :

```dotenv
JWT_SECRET=remplacer-par-une-cle-aleatoire-d-au-moins-32-octets
JWT_ISSUER=project-exemple
JWT_AUDIENCE=project-exemple-api
```

Générer une clé : `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` ; ne jamais publier le secret. L'API doit être déployée en HTTPS.

## Endpoints

- `POST /api/login` : body JSON `{"email":"...","password":"..."}`, renvoie un token Bearer (15 min).
- `GET /api/profile` : header `Authorization: Bearer ...`, `grants: ['ROLE_USER']`.
- 401 si absence ou invalidité du JWT, 403 si droits insuffisants.

## Tests

```bash
php tests/jwt_authentication_test.php
php tests/jwt_api_routes_test.php
php tests/login_input_test.php
```

## Démarrage de l'API

Le front controller `public/index.php` se limite à :

```php
require dirname(__DIR__) . '/vendor/autoload.php';
(new App\\Kernel\\Application(dirname(__DIR__)))->run();
```

- `src/Kernel/ApiBootstrap.php` charge l'environnement et configure les en-têtes JSON.
- `src/Kernel/Application.php` orchestre la création du conteneur, du routeur et son exécution.
- `src/Kernel/RouterFactory.php` configure la résolution des contrôleurs et middlewares, l'initialisation du contexte d'authentification, les grants et la découverte des routes.
- `src/Kernel/ApiErrorHandler.php` traduit les exceptions en réponses JSON 401 / 403 / 404 / 405 / 500, sans exposer les détails internes.

**Aucune session PHP n'est démarrée** et aucun mécanisme de cookie n'est utilisé pour authentifier les appels API. Le conteneur d'autowiring et la librairie de routing restent indépendants de ce bootstrap applicatif.

Consulter `docs/jwt-api.md` pour les mécanismes et limites de sécurité. La branche `main` est dédiée au Web session + CSRF ; l'ancien POC mixte reste disponible dans `archive/web-api-combined-2026-11-05`.

Le nouveau test `php tests/api_kernel_test.php` vérifie la configuration de la factory et les réponses d'erreur JSON.
