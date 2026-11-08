# Cas d'utilisation : prévenir les IDOR sur les emprunts

La table `lending` du projet associe un emprunt à `user_id`. Un utilisateur
authentifié possédant `ROLE_USER` ne doit accéder qu'à ses propres emprunts.

## Architecture

- **Router / middleware** : authentifie et vérifie les grants généraux.
- **Controller** : lit l'identifiant d'emprunt depuis la route ; obtient **l'identité
  authentifiée côté serveur** (session dans `main`, JWT vérifié dans la variante API).
- **LendingService** : orchestre les cas d'utilisation `show`, `listMine`,
  `returnBook` sans dépendance à HTTP.
- **LendingRepository** : restreint chaque requête SQL au `user_id` autorisé,
  y compris les mises à jour.

L'identifiant du propriétaire **ne doit jamais** provenir d'un champ formulaire,
d'un paramètre d'URL ou d'un corps JSON fourni par le client.

## Exemple d'appel depuis un controller

```php
// Après authentification et contrôle ROLE_USER, avec une session valide :
$ownerId = (int) $_SESSION['user']['id'];
$loan = $this->lendingService->show($lendingId, $ownerId);
```

Pour l'API, utiliser l'identifiant issu des claims **validés** du JWT, et non
du corps de requête.

Le contrôleur et le gestionnaire d'erreurs doivent convertir
`LendingNotFoundException` en HTTP **404**, sans révéler l'existence des
emprunts d'un autre utilisateur. Une politique métier plus complexe
(ex. bibliothécaire habilité à voir tous les emprunts) pourra ultérieurement
être isolée dans une `LendingAccessPolicy` dédiée : ne pas contourner le
filtre propriétaire en ajoutant seulement une exception côté controller.

Le retour d'emprunt utilise un `UPDATE` conditionnel atomique. Le projet
historique définit `status` comme un booléen : l'exemple suppose
`1 = emprunt actif` et `0 = rendu`. Cette convention devra être confirmée
avant d'exposer l'action dans une interface publique.

## Tester

```bash
php tests/lending_idor_test.php
```

Le test utilise SQLite en mémoire (extension `pdo_sqlite`) sans toucher à
la base MySQL de développement. Il teste deux utilisateurs, des lectures
autorisées et refusées, le filtrage des listes et les tentatives de retour
d'une ressource étrangère. Si SQLite est absent, le test est ignoré.

## Portée

Ces cas d'utilisation sont prêts à être appelés mais **aucune route HTTP
n'est ajoutée** : cette PR ne présume pas d'une interface de prêts déjà
existante ni de sa gestion CSRF. Pour un POST de retour, protéger la route
avec le middleware CSRF déjà présent, en plus des grants et du contrôle
de propriété dans le service.

Aucune modification des librairies routing, autowiring et validation.
