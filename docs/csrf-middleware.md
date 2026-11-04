# Protection CSRF par middleware

Cette application utilise le pipeline générique de `mithridatem/router`. Le routeur ne contient aucune logique CSRF.

## Composition

- `App\Security\CsrfTokenManager` conserve la génération et la validation des tokens par identifiant de formulaire en session PHP.
- `App\Middleware\CsrfMiddleware` valide les soumissions POST avant le contrôleur, pour les routes explicitement protégées.
- `App\Middleware\ContainerMiddlewareResolver` adapte le conteneur d'autowiring au contrat du routeur.
- `App\Middleware\InvalidCsrfTokenException` signale un refus de sécurité, traduit en HTTP 403 au point d'entrée.

```php
#[Route(path: '/login', method: 'POST', middleware: [CsrfMiddleware::class])]
public function login(ParameterCollectionRequestInterface $request): mixed { /* ... */ }
```

Le formulaire GET continue à générer son token. Pour `/register`, les routes GET et POST pointent sur la même action mais seule la déclaration POST reçoit le middleware.

Les identifiants `login` et `register` sont déterminés côté serveur dans `CsrfMiddleware::FORMS`. Pour ajouter un formulaire protégé, compléter cette correspondance **et** déclarer le middleware sur la route POST. Ne jamais récupérer l'identifiant de formulaire depuis les données POST.

### En cas de token invalide

Le contrôleur n'est pas exécuté. `public/index.php` renvoie une réponse HTTP 403 et un message HTML échappé. Cela remplace volontairement le comportement précédent de réaffichage du formulaire dans le contrôleur. Une future abstraction de gestion des erreurs pourra fournir une page 403 dédiée.

### Tests

```bash
php tests/csrf_token_manager_test.php
php tests/csrf_middleware_test.php
php tests/auth_form_render_test.php
php tests/form_submission_test.php
```

Le test middleware vérifie l'acceptation des tokens corrects, le rejet des tokens absents/malformés/incorrects, l'isolation entre formulaires et le non-déclenchement du contrôleur en cas de refus.
