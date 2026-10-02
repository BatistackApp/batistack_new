# Mise à niveau vers BATISTACK 1.0.0 RC

## Avant la mise à niveau

1. Identifier la version et le commit actuels.
2. Lire les limitations connues.
3. Vérifier l'espace disque et l'état des queues.
4. Mettre l'application en maintenance selon la procédure d'exploitation.
5. Réaliser une sauvegarde complète selon [backup-restore.md](./backup-restore.md).

## Déploiement du code

```bash
git fetch --tags origin
git checkout v1.0.0-rc.1
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

## Migrations et cache

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Ne jamais utiliser `migrate:fresh` ou `migrate:refresh` sur une base contenant des données de production.

## Vérifications post-migration

- `php artisan migrate:status` indique toutes les migrations exécutées.
- Connexion et permissions fonctionnent.
- Les tiers, factures, stocks, salariés, bulletins et écritures sont présents.
- Les queues, mails, PDF, portails et tâches planifiées fonctionnent.
- Les logs ne contiennent aucune erreur bloquante.
