# Routes réservées aux invités et navigation Web

Le profil Web utilise `AuthenticationAwareGrantChecker` de `mithridatem/router:^1.7`.
Le routeur reste indépendant des sessions ; `RouterFactory` fournit la
fonction de vérification de l'authentification à partir de
`$_SESSION['user']['id']`.

## Routes de connexion et inscription

Les routes GET et POST de `/login` et `/register` sont réservées
aux visiteurs non connectés via `grants: ['IS_GUEST']`.

```php
#[Route(path: '/', method: 'GET', grants: ['IS_GUEST'])]
#[Route(path: '/', method: 'POST', grants: ['IS_GUEST'], middleware: [CsrfMiddleware::class])]
```

Le middleware CSRF reste **uniquement** sur POST. Les règles
`IS_AUTHENTICATED` et les rôles `ROLE_USER` / `ROLE_ADMIN`
restent disponibles pour les autres routes.

## Redirection des utilisateurs déjà connectés

`GuestNavigation`, dans la couche applicative, retient en session la
dernière page GET effectivement exécutée et encore autorisée.
Elle n'utilise jamais l'en-tête HTTP `Referer` et refuse les chemins externes.

Lorsqu'un utilisateur connecté tente d'accéder à une route
`IS_GUEST`, `Application` cherche une destination locale sûre :
dernière page autorisée ou repli `/`. Si une destination existe,
elle retourne une redirection **HTTP 303** ; sinon, le refus **403**
est conservé.

Les refus de rôles ordinaires restent des **403**, sans redirection.

Cette politique s'applique uniquement à la variante Web à session ;
elle n'est pas utilisée par `variant/api-jwt`.

## Contrôles

```bash
php tests/web_kernel_test.php
php tests/csrf_route_configuration_test.php
php tests/guest_navigation_test.php
```

En test manuel : ouvrir `/login` et `/register` en tant qu'invité,
vérifier le CSRF sur POST, puis se connecter et essayer à nouveau GET
et POST sur ces deux chemins. Une route soumise à un rôle manquant
doit continuer à renvoyer 403.
