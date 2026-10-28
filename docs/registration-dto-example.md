# Exemple : inscription avec DTO et service orchestrateur

Ce projet montre comment intégrer les librairies Routing, Validation et Autowiring dans une application PHP.

## Séparation des responsabilités

- `RegisterController` lit les données HTTP et construit un `RegisterUserInput`.
- `RegisterUserInput` porte les contraintes du **cas d'utilisation d'inscription**, y compris la confirmation.
- `UserService::register(RegisterUserInput $input)` orchestre validation, unicité, création, persistance.
- `PasswordHasher` assure le hachage et la vérification.
- `User` reste l'entité persistée, et `UserRepository` s'occupe de SQL.

```text
POST /register
  -> RegisterController
     -> RegisterUserInput::fromArray(...)
     -> UserService::register($input)
        -> Validator::validate($input)
        -> UserRepository::findByEmail(...)
        -> PasswordHasher::hash(...)
        -> UserRepository::create(...)
```

Les erreurs sont retournées sous les clés `validation` / `errors` et le succès sous `success`, comme dans l'exemple d'origine. L'ancien `UserService::addUser(Request $request)` demeure accessible pour une migration progressive, mais le contrôleur utilise désormais le DTO.

## Exemple sans contrôleur HTTP

```php
use App\DTO\RegisterUserInput;

$input = RegisterUserInput::fromArray([
    'firstname' => 'Ada',
    'lastname' => 'Lovelace',
    'email' => 'ada@example.test',
    'password' => 'secret123',
    'confirm_password' => 'secret123',
]);

$result = $container->get(\App\Service\UserService::class)->register($input);
```

Le conteneur résout les trois dépendances concrètes du service et sa configuration PDO existante.

## Validation DTO vs entité

Les règles propres à l'inscription (champs requis, forme de l'email, longueur du **mot de passe brut**, confirmation) sont déclarées sur le DTO. Les invariants transversaux à tous les usages de l'entité peuvent rester protégés dans `User`. Ne jamais transformer les caractères du mot de passe avec `htmlspecialchars` ni le tronquer avant hachage ; l'échappement HTML se fait dans la vue.

## Tests rapides

Après `composer install`, lancer :

```sh
php tests/register_user_input_test.php
```

Ce script vérifie la normalisation, la gestion des types inattendus, les contraintes du DTO et le hachage. Il ne remplace **pas** un test d'intégration HTTP/SQL : vérifier l'inscription réelle avec une base MySQL de test avant fusion.

## Points à durcir ensuite

- Vérifier explicitement les erreurs d'insertion du repository et retourner une erreur si la persistance échoue.
- Traduire les violations de contrainte `UNIQUE(email)` en erreur métier en cas d'inscriptions concurrentes.
- Envisager un résultat métier typé et des interfaces pour le repository et le hasher lorsque les tests de service deviennent nécessaires.
