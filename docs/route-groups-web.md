# Groupes de routes — variante Web

Les contrôleurs `LoginController` et `RegisterController` utilisent `#[RouteGroup]` depuis `mithridatem/router` 1.6.

```php
#[RouteGroup(prefix: '/login')]
final class LoginController extends AbstractController
{
    #[Route(path: '/', method: 'GET', grants: [])]
    public function showLogin(): mixed { /* rendu */ }

    #[Route(path: '/', method: 'POST', grants: [], middleware: [CsrfMiddleware::class])]
    public function login(ParameterCollectionRequestInterface $request): mixed { /* traitement */ }
}
```

Le préfixe de classe `/login` et le chemin relatif `/` produisent l'URL publique `/login`. `RegisterController` suit le même principe avec `/register`.

Les formulaires d'authentification restent accessibles publiquement (grants vides). **Le middleware CSRF est appliqué uniquement aux routes POST** : il ne doit pas être posé au niveau du groupe, car il refuserait les GET.

Le token CSRF est généré dans le contrôleur pour le chemin de soumission complet (`$this->csrf->generate('/login')`) et contrôlé par le middleware depuis `RequestInterface::getPath()`. Aucun registre de formulaires n'est nécessaire. Détails : [`csrf-middleware.md`](csrf-middleware.md).

Pour vérifier la configuration :

```bash
composer install
php tests/csrf_route_configuration_test.php
php tests/web_kernel_test.php
php tests/csrf_middleware_test.php
```
