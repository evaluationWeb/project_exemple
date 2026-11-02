# Refactorisation CSRF et soumission des formulaires

## Principe

- `AbstractController::isFormSubmitted()` vérifie seulement la méthode HTTP POST,
  indépendamment de la présence d'un bouton `submit`.
- `CsrfTokenManager` génère et vérifie un token par identifiant de formulaire
  (`login`, `register`), stocké dans la session PHP.
- `LoginController` et `RegisterController` vérifient le CSRF avant de
  transmettre les données aux services applicatifs.
- Le token est renvoyé à la vue sur chaque rendu, y compris en cas de validation
  échouée, afin que l'utilisateur puisse corriger son formulaire.

## Tests

```sh
composer install
php tests/form_submission_test.php
php tests/csrf_token_manager_test.php
```

## Vérifications manuelles

1. `GET /login` et `GET /register` doivent afficher un champ caché CSRF.
2. Soumettre un token correct mais des données invalides : la page réaffichée
   doit conserver un token non vide et permettre une seconde soumission.
3. Soumettre `csrf_token[]=unexpected`, un token absent ou un token modifié :
   la demande doit être rejetée sans TypeError ni appel au service métier.
4. `POST /register` sans champ `submit` : la soumission doit être reconnue.
5. Une inscription ou une connexion valide doit fonctionner avec une session
   PHP active et une base de données correctement configurée.

Ce changement reste volontairement simple : il ne crée ni middleware ni
nouvelle abstraction de session.
