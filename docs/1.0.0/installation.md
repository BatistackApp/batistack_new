# Installation BATISTACK 1.0.0

## Prérequis

- PHP 8.3 ou supérieur avec `fileinfo` et `zip`.
- Composer 2.
- Node.js et npm.
- Une base supportée et un disque de stockage persistant.
- Chromium disponible pour Browsershot si les PDF sont activés.

## Installation

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
```

Configurer ensuite les variables de `.env` adaptées à l'environnement : base de données, mail, stockage, queue, PDF, signature, banque et paiement.

## Vérification

```bash
php artisan about
php artisan migrate:status
php artisan config:clear
```

Vérifier la connexion, les permissions, la génération PDF, le stockage des documents et le traitement d'une queue.
