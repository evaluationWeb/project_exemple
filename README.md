# project_exemple
Projet PHP POO d'exemple d'intégration des 3 librairies `evaluationWeb/routing`, `evaluationWeb/validation` et `mithridatem/autowiring`  

**Prés requis** : 
```
PHP 8.2 +
Composer 2.5 +
Mysql
```

## 1 récupération du projet
```sh
git clone https://github.com/evaluationWeb/project_exemple.git
```

## 2 Créer ou éditer le fichier `.env` avec vos informations
```dotenv
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=library_project
DATABASE_USERNAME=
DATABASE_PASSWORD=
```

## 3 Créer la base de données avec le script db.sql

## 4 installer les dépendances :
```sh
composer install
```

## 5 démarrer le projet :
```sh
php -S 127.0.0.1:8000 -t public
```
