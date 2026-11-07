# Audit JWT — variante API (2026-11-07)

## Portée

Revue de `variant/api-jwt` : `JwtTokenManager`, `JwtMiddleware`,
`AuthenticationContext`, `RequestAuthenticationMiddleware`,
`ContextGrantChecker`, configuration des routes et traitement des erreurs.

Les tests adversariaux supplémentaires sont dans
`tests/jwt_security_edge_test.php` et s'exécutent sans MySQL.

## Garanties caractérisées par les tests

- Les signatures avec une autre clé ou un algorithme différent de HS256
  sont refusées.
- Les claims `iss`, `aud`, `sub`, `email`, `jti`, `iat`, `nbf`,
  `exp` et `roles` sont contrôlés pour les scénarios couverts.
- Un token sans `ROLE_USER` ne permet pas l'accès à une route demandant
  ce rôle.
- Une identité n'est pas réutilisée après un token invalide, un refus
  d'autorisation ou un dispatch antérieur réussi.
- Les routes publiques (`/api/login`) et privées
  (`/api/profile`) sont déclarées dans des groupes distincts ;
  le middleware JWT protège les actions privées.
- `ApiErrorHandler` renvoie des erreurs JSON génériques et évite
  l'exposition de détails d'exception.

## Limites et points de vigilance

1. **Configuration du TTL :** le constructeur de `JwtTokenManager`
   vérifie la longueur du secret, l'issuer et l'audience, mais ne
   contraint pas explicitement `$ttl` à être strictement positif.
   La configuration doit imposer une durée positive et raisonnable ;
   un contrôle explicite pourrait être proposé dans une PR distincte
   après test et analyse d'impact.
2. **Clé et déploiement :** une longueur minimale de secret ne garantit
   pas sa qualité aléatoire. Déployer une clé générée
   cryptographiquement et ne pas l'inclure dans Git.
3. **Rôles dans le JWT :** ils reflètent l'état au moment de l'émission.
   Une suppression de droits en base ne révoque pas un token déjà
   émis. Durée de vie, rotation et révocation sont à concevoir selon
   le besoin métier.
4. **Connexion :** limitation de débit et prévention du credential
   stuffing à traiter au niveau applicatif et/ou reverse proxy.
5. **HTTPS :** obligatoire pour protéger le transport des tokens ;
   éviter de les stocker dans des URLs ou journaux.
6. **Tests restant à faire :** vraie requête HTTP avec serveur,
   compte de base de données, reverse proxy, politiques CORS et
   limites de taille de corps.

Cette passe ne démontre pas l'absence de vulnérabilités et ne modifie
pas le code de production.
