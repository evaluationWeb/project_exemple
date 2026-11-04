# project_exemple
Projet PHP POO d'exemple d'intégration des 3 librairies `evaluationWeb/routing`, `evaluationWeb/validation` et `mithridatem/autowiring`  

**Prés requis** : 
```
PHP 8.2 +
Composer 2.5 +
Mysql
```

## 1 récupération du projet
```sh
git clone https://github.com/evaluationWeb/project_exemple.git
```

## 2 Créer ou éditer le fichier `.env` avec vos informations
```dotenv
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=library_project
DATABASE_USERNAME=
DATABASE_PASSWORD=
```

## 3 Créer la base de données avec le script db.sql

## 4 installer les dépendances :
```sh
composer install
```

## 5 démarrer le projet :
```sh
php -S 127.0.0.1:8000 -t public
```

## Requêtes HTTP et DTO (router ^1.4)

Les actions POST de `RegisterController` et `LoginController` utilisent
`ParameterCollectionRequestInterface::allPost()` pour construire les DTO via
`fromArray()`. La validation CSRF précède toujours l'appel du service.
Les services reçoivent uniquement les DTO et restent indépendants de HTTP.
Le corps brut JSON reste accessible séparément avec `getContent()`.

Test de correspondance des paramètres et DTO :
```sh
php tests/request_dto_mapping_test.php
```


## Middleware CSRF

Les soumissions POST `/login` et `/register` sont protégées par `CsrfMiddleware` avant l'exécution du contrôleur ; les formulaires GET restent accessibles. L'implémentation et les tests sont décrits dans [docs/csrf-middleware.md](docs/csrf-middleware.md).


## JWT API authentication

`POST /api/login` consumes JSON credentials and returns a 15-minute HS256 access token; `GET /api/profile` requires `Authorization: Bearer <token>` and `ROLE_USER`. Session-based web login and CSRF remain unchanged. Set `JWT_SECRET` (at least 32 unpredictable bytes), optionally `JWT_ISSUER` and `JWT_AUDIENCE` in the local `.env`. Never commit a secret. See [docs/jwt-api.md](docs/jwt-api.md).
