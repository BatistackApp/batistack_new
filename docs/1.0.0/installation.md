# Installation BATISTACK 1.0.0

Ce guide décrit une installation neuve. Pour une instance existante, suivre [la procédure de mise à niveau](./upgrade.md). Ne pas exécuter les commandes de création ou de seed sur une base de production existante.

## Prérequis

Les contraintes Composer du dépôt (composer.json) sont la référence :

- PHP 8.3 ou supérieur, avec les extensions fileinfo et zip, ainsi que les extensions requises par Laravel et ses dépendances.
- Composer 2.
- Node.js et npm compatibles avec le lockfile du dépôt.
- MySQL/MariaDB ou autre moteur effectivement validé pour l'environnement cible. Vérifier la version et la compatibilité avant installation.
- Un compte système dédié à l'application, sans droits administrateur.
- Un stockage persistant et sauvegardé pour les fichiers utilisateurs.
- Chromium et Node.js accessibles par le processus PHP si les fonctions PDF utilisant Browsershot sont activées.
- Les identifiants et contrats des services externes réellement utilisés.

Le dépôt utilise Laravel 13, Filament 5 et Livewire 4. La version exacte de PHP et des dépendances doit rester cohérente avec composer.lock et package-lock.json.

## Récupération du code

    git clone https://github.com/BatistackApp/batistack_new.git
    cd batistack_new

Pour une installation de production, déployer un tag ou un commit de release approuvé, pas la branche de développement.

## Dépendances et configuration

    composer install --no-interaction --prefer-dist --optimize-autoloader
    npm ci
    npm run build

Créer le fichier d'environnement et générer une clé uniquement pour une nouvelle installation :

    cp .env.example .env
    php artisan key:generate

Éditer ensuite .env avant toute exposition réseau. Le fichier .env.example est une configuration de développement : il utilise notamment APP_ENV=local, APP_DEBUG=true, SQLite et un mailer de journalisation. **Ne pas le reprendre tel quel en production.**

Configurer au minimum :

- APP_NAME, APP_ENV=production, APP_DEBUG=false, APP_URL, APP_KEY.
- DB_CONNECTION et les paramètres de connexion à la base.
- SESSION_DRIVER, CACHE_STORE et QUEUE_CONNECTION selon les services retenus.
- FILESYSTEM_DISK et les paramètres du stockage privé/public.
- Le transport mail et une adresse expéditeur valide.
- Les chemins BROWSERSHOT_* si les PDF sont activés.
- Les secrets des intégrations activées. Ne jamais versionner ces secrets.

Conserver .env hors du dépôt et limiter ses permissions au compte applicatif.

## Base de données

Sur une base neuve uniquement :

    php artisan migrate --force

Les seeders sont destinés à l'initialisation contrôlée d'un environnement neuf. Ne pas exécuter db:seed en production sans avoir identifié précisément les seeders et validé leurs effets.

Ne jamais utiliser migrate:fresh, migrate:refresh ou db:wipe sur une base contenant des données à conserver.

## Stockage et permissions

Le disque local privé est configuré sous storage/app/private et le disque public sous storage/app/public. Le lien public Laravel pointe vers storage/app/public.

    php artisan storage:link

Le compte exécutant PHP doit pouvoir écrire dans les répertoires nécessaires de storage et bootstrap/cache. Appliquer des propriétaires et groupes adaptés au serveur web ; ne pas utiliser chmod -R 777.

## Initialisation et vérifications

    php artisan optimize:clear
    php artisan about
    php artisan migrate:status
    php artisan route:list

Vérifier ensuite, dans l'environnement cible : authentification, rôles et permissions ; accès à la base ; lecture/écriture sur les disques ; génération PDF si activée ; envoi d'un mail ; traitement d'un job ; exécution du scheduler ; accès aux portails et isolation des données.

Consulter [le guide de déploiement](./deployment.md), [les limitations connues](./known-limitations.md) et [la checklist GO / NO-GO](../avancement/v1.0.0-go-no-go.md).
