# Configuration des environnements

## Règle générale

`src/Kernel/EnvironmentLoader.php` sélectionne la source de configuration à partir de `APP_ENV` **défini avant le démarrage PHP** :

| `APP_ENV` | Source |
| --- | --- |
| `dev` (par défaut) | `.env` local facultatif |
| `test` | `.env.test` local facultatif |
| `prod` | Variables d'environnement du serveur/conteneur **uniquement** |

En production, **aucun fichier `.env`, `.env.test` ou `.env.prod` n'est lu**. Les valeurs d'environnement fournies par le serveur ont priorité sur celles d'un fichier local. Une valeur inconnue de `APP_ENV` provoque une erreur explicite.

## Développement

```bash
cp .env.example .env
# Compléter les valeurs privées dans .env
composer install
php -S 127.0.0.1:8000 -t public
```

Le fichier `.env` ne doit jamais être commité ni placé sous `public/`. `.env.example` ne contient que des valeurs fictives.

Pour tester le chargement de `.env.test`, définir `APP_ENV=test` **dans l'environnement du processus**. Le paramètre `APP_ENV` situé dans `.env.test` ne peut pas sélectionner lui-même ce fichier.

## Production

Configurer `APP_ENV=prod` **hors du projet**, ainsi que toutes les variables nécessaires à l'application, dans la configuration PHP-FPM/Apache, dans l'orchestrateur de conteneurs ou dans les secrets du fournisseur d'hébergement.

Par exemple, un conteneur peut recevoir `APP_ENV=prod` et les secrets par injection d'environnement. Aucun fichier `.env.prod` n'est requis ni chargé. Vérifier que PHP-FPM transmet réellement les variables à PHP (notamment `clear_env` / la configuration `env[]` selon l'hébergement).

Ne pas publier de valeurs sensibles dans le dépôt, les images Docker, les logs ou le répertoire public ; limiter les permissions de lecture des secrets et utiliser HTTPS. L'absence de fichier `.env` ne protège pas contre une configuration du serveur exposant des fichiers privés : le document root doit rester `public/`.

**Important :** l'ancien fichier `.env` était suivi par Git. Il a été retiré du suivi dans ces PR, mais reste dans l'historique Git. Si des secrets réels y ont été publiés, les révoquer/faire tourner et envisager un nettoyage de l'historique Git selon vos procédures.

## Vérification

```bash
php tests/environment_loader_test.php
```

Ce test vérifie le chargement en développement/test, la priorité des variables injectées et le refus de lire des fichiers locaux en production.

Variables Web : `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USERNAME`, `DATABASE_PASSWORD`, `UPLOAD_DIRECTORY`.
