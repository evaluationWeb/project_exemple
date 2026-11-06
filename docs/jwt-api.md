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

Le token HS256 est valide pendant **900 secondes par défaut** (configurable par `JWT_TTL_SECONDS` dans `.env`) et contient `iss`, `aud`, `sub`, `iat`, `nbf`, `exp`, `jti`, `email` et `roles`. La signature et les claims sont contrôlés par le gestionnaire avant de constituer l'identité. Les rôles du token sont une photographie à l'émission : les changements en base ne sont pas visibles avant expiration.

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

## Déclarer les routes avec RouteGroup

Les deux contrôleurs de l'API partagent le préfixe public `/api`, mais **pas** les exigences d'authentification :

```php
// AuthController : connexion publique.
#[RouteGroup(prefix: '/api')]
final class AuthController
{
    #[Route(path: '/login', method: 'POST')]
    public function login(RequestInterface $request): void { /* ... */ }
}

// ProfileController : toutes ses actions héritent du JWT et de ROLE_USER.
#[RouteGroup(prefix: '/api', grants: ['ROLE_USER'], middleware: [JwtMiddleware::class])]
final class ProfileController
{
    #[Route(path: '/profile', method: 'GET')]
    public function profile(): void { /* ... */ }
}
```

La route publique `/api/login` **ne doit pas** hériter d'un groupe protégé. `grants: []` sur une route ne désactive pas les grants d'un groupe : les routes publiques doivent rester dans un groupe distinct. Une route ayant des grants non vides remplace les grants du groupe (elle ne les additionne pas).

Le pipeline reste : initialisation globale de l'identité (`RequestAuthenticationMiddleware`), authentification JWT sur les routes privées (`JwtMiddleware`), contrôle des grants, contrôleur. Un appel à `/api/profile` sans Bearer valide produit un **401**, tandis qu'un token authentifié sans `ROLE_USER` produit un **403**. La gestion des réponses JSON reste dans `ApiErrorHandler`.

**Ajouter une nouvelle action privée** dans `ProfileController` lui fait hériter du middleware JWT et de `ROLE_USER` sans répéter leurs déclarations. **Ajouter une action publique** exige un contrôleur/groupe non protégé (par exemple `AuthController`). Ne pas placer `JwtMiddleware` sur l'ensemble du préfixe `/api`.

## Séparation des responsabilités

- `JwtTokenManager` : émission, vérification cryptographique, claims obligatoires et durée de vie.
- `JwtMiddleware` : lecture du Bearer et authentification.
- `AuthenticationContext` : identité JWT durant le dispatch, réinitialisée en entrée/sortie par `RequestAuthenticationMiddleware`.
- `ContextGrantChecker` : utilise l'identité JWT vérifiée ou, avec la sémantique existante d'`ArrayGrantChecker`.
- `AuthController` et `ProfileController` : logique HTTP / JSON.

**Limite volontaire du POC :** aucun refresh token, aucune révocation. Ne pas envoyer un JWT dans un cookie. Une route API protégée doit déclarer `JwtMiddleware` explicitement **ou l'hériter d'un `RouteGroup` protégé** : elle ne doit jamais s'appuyer sur les cookies de session Web. Avant de mettre en production, évaluer rotation des clés, révocation, CORS, rate limiting des connexions, stockage côté client et déploiement en HTTPS.

## Tests

```bash
php tests/jwt_authentication_test.php
php tests/jwt_api_routes_test.php
```
