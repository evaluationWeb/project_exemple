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

Consulter `docs/jwt-api.md`. La branche `main` conserve l'exemple complet avec la version Web.
