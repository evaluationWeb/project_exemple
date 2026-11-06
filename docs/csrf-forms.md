# Gestion des formulaires : HTTP POST et CSRF

## Décisions pédagogiques

- `AbstractController::isFormSubmitted(RequestInterface)` teste exclusivement la méthode HTTP `POST`, sans dépendance à un champ `submit`.
- `CsrfTokenManager` reste dans `App\Security` et vérifie un token lié au chemin public de soumission du formulaire (par exemple `/login` ou `/register`).
- `LoginController` et `RegisterController` transmettent un token CSRF à leurs vues lors de **chaque rendu**, même après une erreur.
- Le contrôle CSRF est fait avant l'appel au service applicatif. La validation des données reste dans les DTO/services.

## Tests automatisés

```sh
composer install
php tests/form_submission_test.php
php tests/csrf_token_manager_test.php
```

## Parcours à vérifier manuellement

1. `GET /login` et `GET /register` : le champ caché `csrf_token` est présent et non vide.
2. Identifiants invalides sur `POST /login` : le formulaire réaffiché contient toujours un token valide ; nouvelle soumission possible.
3. Données invalides sur `POST /register` : le formulaire peut être soumis de nouveau.
4. Token absent, falsifié, `csrf_token[]=foo` : rejet sans appel au service ni `TypeError`.
5. `POST /register` sans champ `submit` : la requête est bien reconnue comme soumission.
6. Vérifier connexion et inscription avec la base MySQL configurée.

Le token CSRF reste stable pendant la session et n'est pas à usage unique.
