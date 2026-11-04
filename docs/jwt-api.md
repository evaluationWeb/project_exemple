# Authentification JWT (POC)

Cette évolution concerne **project_exemple** : `router-librairie` conserve ses middlewares génériques et ses grants. Cette branche ne fournit aucune interface Web ni session PHP.

## Configuration

```bash
composer install
# Dans .env (NE PAS COMMITTER ce fichier)
JWT_SECRET="une-cle-aleatoire-de-32-octets-minimum"
JWT_ISSUER="project-exemple"
JWT_AUDIENCE="project-exemple-api"
```

Générer le secret avec `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Utiliser HTTPS pour toute utilisation réelle. La configuration est évaluée à la construction de `JwtTokenManager` (l'accès au site Web n'exige pas le secret).

Le token HS256 est valide pendant **900 secondes** et contient `iss`, `aud`, `sub`, `iat`, `nbf`, `exp`, `jti`, `email` et `roles`. La signature et les claims sont contrôlés par le gestionnaire avant de constituer l'identité. Les rôles du token sont une photographie à l'émission : les changements en base ne sont pas visibles avant expiration.

## API

```bash
curl -X POST http://localhost/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.test","password":"password"}'

curl http://localhost/api/profile \
  -H 'Authorization: Bearer VOTRE_TOKEN'
```

- `POST /api/login` retourne `access_token`, `token_type` et `expires_in`. La validation des identifiants réutilise `LoginService`; aucune identité de session Web n'est créée par cet endpoint (la branche API ne démarre pas de session PHP).
- `GET /api/profile` exige un JWT valide et `ROLE_USER`.
- Erreurs : 401 pour JWT manquant/invalide et login échoué, 403 pour droits insuffisants.

## Séparation des responsabilités

- `JwtTokenManager` : émission, vérification cryptographique, claims obligatoires et durée de vie.
- `JwtMiddleware` : lecture du Bearer et authentification.
- `AuthenticationContext` : identité JWT durant le dispatch, réinitialisée en entrée/sortie par `RequestAuthenticationMiddleware`.
- `ContextGrantChecker` : utilise l'identité JWT vérifiée ou, avec la sémantique existante d'`ArrayGrantChecker`.
- `AuthController` et `ProfileController` : logique HTTP / JSON.

**Limite volontaire du POC :** aucun refresh token, aucune révocation. Ne pas envoyer un JWT dans un cookie. Une route API doit déclarer explicitement `JwtMiddleware` si elle doit être protégée : elle ne doit pas s'appuyer sur les cookies de session Web. Avant de mettre en production, évaluer rotation des clés, révocation, CORS, rate limiting des connexions, stockage côté client et déploiement en HTTPS.

## Tests

```bash
php tests/jwt_authentication_test.php
php tests/jwt_api_routes_test.php
```
