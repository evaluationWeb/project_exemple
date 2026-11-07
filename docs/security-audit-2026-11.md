# Audit de sécurité applicatif — project_exemple (2026-11-07)

> **Suivi — correctifs proposés :** la PR de correction remplace l'affichage direct des
> erreurs PDO par leur propagation vers le gestionnaire HTTP générique,
> normalise les dates SQL pendant l'hydratation et transforme le
> reproducteur manuel en test de non-régression exécuté dans la CI.
> Les observations ci-dessous décrivent l'état au moment de l'audit initial.

## Portée et méthode

Revue statique de `main` (Web/session/CSRF) et des composants significatifs
de `variant/web-session-csrf` et `variant/api-jwt`.
Les bibliothèques `mithridatem/router`, `mithridatem/autowiring` et
`mithridatem/validation` ont fait l'objet d'audits distincts.
Aucun test complet de déploiement HTTP/SQL ni pentest distant n'est revendiqué.

Cette première PR consigne les constats et un **reproducteur local isolé** :
`php tests/security/reproduce_repository_leak.php`. Le script simule des
exceptions PDO, sans serveur MySQL ni données sensibles réelles. Il n'est pas
intégré à la CI : il caractérise deux défauts courants **avant leur correction**.
Ne pas utiliser un test qui exige la persistance d'un défaut comme test
de non-régression de sécurité après correction.

## Constats prioritaires

### S-01 — Messages d'erreur PDO rendus au client (risque de fuite d'information)

**Emplacement** : `src/Repository/UserRepository.php`, `find()` et
`findAll()`.

Les branches `catch (PDOException)` exécutent
`echo $e->getMessage()`. Lorsqu'une exception survient à la préparation
ou à l'exécution SQL, la sortie peut contenir des détails de base de données,
de requête ou de configuration. Ces octets sont émis directement pendant
l'exécution et ne passent pas par `WebErrorHandler` qui masque les erreurs 500.

**Reproduction** : `tests/security/reproduce_repository_leak.php` instancie
un PDO de test dont `prepare()` et `query()` lèvent une `PDOException`
contenant une chaîne de diagnostic non secrète. Les méthodes de repository
renvoient actuellement cette chaîne sur la sortie standard.

**Impact** : fuite d'informations techniques si ces méthodes sont appelées
sur un chemin HTTP et si une erreur PDO survient. Gravité contextuelle
(moyenne, pouvant augmenter selon les données divulguées).

**Recommandation (PR distincte)** : ne jamais afficher une exception SQL
depuis une couche repository ; laisser remonter une exception vers un
gestionnaire HTTP générique et journaliser côté serveur, ou renvoyer un
résultat d'échec explicite selon le contrat métier. Prévoir un test de
non-divulgation qui attend l'absence de sortie SQL après correction.

### F-01 — Type de `updated_at` non normalisé lors de l'hydratation

**Emplacement** : `src/Entity/User.php::hydrate()`.

La méthode transfère `updated_at` à `setUpdatedAt(?DateTimeImmutable)`
sans conversion. Une date retournée par PDO au format SQL (chaîne non
nulle) provoque un `TypeError`. `UserRepository::findByEmail()`
utilise ce parcours, notamment lors d'une connexion.

**Reproduction** : même script manuel, avec un tableau d'hydratation
contenant `updated_at = '2026-11-07 12:00:00'`.

**Impact** : indisponibilité de certaines lectures et connexions pour
les lignes ayant une date de mise à jour non nulle. Ce problème est
fonctionnel et **ne démontre pas, à lui seul, une faille de sécurité**.

**Recommandation (PR distincte)** : convertir explicitement les champs
temporels lors de l'hydratation, tester les valeurs `null` et non
nulles, ainsi que les formats invalides.

## Écarts de configuration à arbitrer

### C-01 — Routes invitées sur Web

Dans `main`, les routes `/login` et `/register` ont
`grants: []`, et `RouterFactory` utilise `ArrayGrantChecker`.
Le profil `variant/web-session-csrf` utilise `IS_GUEST` avec
`AuthenticationAwareGrantChecker`. La branche `main` laisse
donc ces formulaires accessibles à un utilisateur déjà connecté.
Cela ne contourne pas en soi l'authentification d'une route protégée,
mais est un **écart fonctionnel** par rapport à la politique cible.
La synchronisation Web doit être traitée après l'audit.

### C-02 — Déploiement des cookies HTTPS

`WebBootstrap` active le cookie de session `Secure` lorsque
`$_SERVER['HTTPS']` est non vide ; ce comportement dépend de la
configuration du reverse proxy et du serveur frontal. En production
HTTPS, vérifier que l'application reçoit une indication fiable de TLS
et émet toujours le cookie avec `Secure`. Les flags `HttpOnly`,
`SameSite=Lax` et `session.use_strict_mode` sont présents.

### C-03 — JWT et limites applicatives

Dans `variant/api-jwt`, `JwtTokenManager` vérifie signature HS256,
issuer, audience, durée de vie, identifiant, rôles et date d'expiration.
`RequestAuthenticationMiddleware` réinitialise le contexte avant
et après l'exécution. Le test du flux complet nécessite encore la
vérification des routes, middlewares, contrôleurs et réponses sur la
branche API. La rotation/révocation des tokens et la protection
contre les essais répétés de connexion restent des questions
d'exploitation, pas des défauts démontrés du code consulté.

## Garanties déjà observées

- Middleware CSRF ciblant les routes POST et associant le token au chemin,
  comparé par `hash_equals()`; les tableaux HTTP sont refusés.
- Session régénérée après connexion et déconnexion ; le hash du mot de
  passe n'est pas stocké dans la session.
- Mots de passe hachés par `password_hash()` et vérifiés par
  `password_verify()`.
- DTO de login/inscription rejetant les tableaux à la place des champs
  textuels et préservant les espaces des mots de passe.
- `WebErrorHandler` échappant les messages HTML et masquant les erreurs
  internes pour les exceptions qui lui parviennent.
- Environnement `prod` n'utilisant pas le chargement de fichiers
  `.env` locaux.

## Suite proposée

1. Corriger S-01 et F-01 dans une **PR spécifique** avec des tests de
   non-divulgation et de conversion de dates.
2. Synchroniser `main` sur la politique Web `IS_GUEST` déjà retenue,
   avec tests des réponses pour utilisateurs authentifiés.
3. Exécuter une revue et les scénarios HTTP JWT sur `variant/api-jwt`
   (tokens expirés, signature, rôles, méthodes, identité).
4. Tester avec une vraie base MySQL et un environnement HTTPS derrière
   proxy avant la synchronisation vers `mvc_script_generator`.

Cette PR est une **photographie de l'état actuel**, pas une
attestation de sécurité ou une correction de ces constats.
