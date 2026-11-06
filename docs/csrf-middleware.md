# Protection CSRF par middleware

Cette application utilise le pipeline générique de `mithridatem/router`. Le routeur ne contient aucune logique CSRF.

## Répartition des responsabilités

- `App\Security\CsrfTokenManager` génère et vérifie des tokens stockés en session, associés à un identifiant serveur.
- `App\Middleware\CsrfMiddleware` vérifie le token reçu dans `csrf_token` **avant** le contrôleur, uniquement sur les routes POST protégées.
- `App\Middleware\ContainerMiddlewareResolver` permet l'injection des dépendances du middleware.
- `App\Middleware\InvalidCsrfTokenException` signale un refus ; `public/index.php` renvoie HTTP 403.

## Convention : l'identifiant CSRF est le chemin de soumission

Le middleware utilise `$context->getPath()` comme identifiant. Le contrôleur doit donc générer le token avec **exactement le chemin public du POST** (et non le chemin relatif de l'attribut `#[Route]`).

Par exemple, avec le préfixe `#[RouteGroup(prefix: '/login')]` et une route `#[Route(path: '/', method: 'POST', ...)]`, l'identifiant est `/login` :

```php
#[RouteGroup(prefix: '/login')]
final class LoginController extends AbstractController
{
    // Sur GET : transmettre le token au template du formulaire.
    // $this->csrf est une instance de CsrfTokenManager.
    private function renderLoginForm(array $data = []): mixed
    {
        return $this->render('login', 'Connexion', [
            ...$data,
            'csrf_token' => $this->csrf->generate('/login'),
        ]);
    }

    #[Route(path: '/', method: 'POST', middleware: [CsrfMiddleware::class])]
    public function login(ParameterCollectionRequestInterface $request): mixed
    {
        // Le middleware a déjà vérifié le champ csrf_token.
        // ...
    }
}
```

Le template doit transmettre le token dans un champ caché :

```html
<form action="/login" method="post">
    <input type="hidden" name="csrf_token" value="...">
    <!-- autres champs du formulaire -->
</form>
```

Dans les vrais templates du projet, la valeur doit être échappée avec `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

## Ajouter un formulaire protégé

1. Déclarer sa route `POST` avec `middleware: [CsrfMiddleware::class]`.
2. Générer le token avec le **chemin public du POST** : par exemple `$this->csrf->generate('/contact')`.
3. Inclure un champ caché nommé `csrf_token` dans le formulaire envoyé vers `/contact`.
4. Tester la soumission avec un token correct et avec un token absent ou incorrect.

**Aucune modification de `CsrfMiddleware` ni ajout dans une constante n'est nécessaire.** Les routes GET ne doivent pas recevoir ce middleware : il exige la méthode POST.

L'identifiant est fixé côté serveur à partir du chemin HTTP, **jamais depuis un champ POST** pouvant être manipulé par le client. Les tokens de `/login` et de `/register` restent distincts.

## Limites de cette convention

- Un chemin dynamique tel que `/articles/42/delete` produit un identifiant différent de `/articles/43/delete`. Il faut donc générer le token avec le chemin **réel** visé par le formulaire.
- Si plusieurs formulaires soumettent vers la **même URL**, ils utilisent le même identifiant et donc le même token CSRF. Pour les distinguer, il faudrait faire évoluer le contrat (métadonnées de route ou identifiant explicite côté serveur).
- Il faut utiliser le même format de chemin lors de la génération et de la validation (préfixes, slash final, sous-répertoire/basePath). Il n'existe pas ici de générateur de noms de routes.
- Le middleware n'est attaché qu'aux routes `POST` : toute autre méthode à effet de bord doit être protégée séparément avant d'être introduite.
- Les tokens sont stables pendant la session ; ils ne sont pas à usage unique.

## En cas de token invalide

Le contrôleur n'est pas exécuté. `public/index.php` renvoie une réponse HTTP 403 et un message HTML échappé.

## Tests

```bash
php tests/csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/csrf_route_configuration_test.php
php tests/auth_form_render_test.php
php tests/form_submission_test.php
```

Le test middleware couvre également `/contact`, une route fictive protégée sans inscription préalable dans le middleware, et le rejet d'un token valable pour une autre route.
