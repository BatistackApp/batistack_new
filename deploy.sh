#!/bin/bash

php artisan down
git reset --hard
git pull origin production

# Récupérer les tags de GitHub
git fetch --tags
# Extraire le dernier tag (ex: v1.0.3)
LATEST_TAG=$(git describe --tags --abbrev=0)
# Mettre à jour ou ajouter APP_VERSION dans le fichier .env
if grep -q "^APP_VERSION=" .env; then
    sed -i "s/^APP_VERSION=.*/APP_VERSION=$LATEST_TAG/" .env
else
    echo "APP_VERSION=$LATEST_TAG" >> .env
fi

# Installer les dépendances PHP et Node sans interaction
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
npm install
npm run build

php artisan migrate --force
php artisan filament:asset
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart

php artisan up

echo "Déploiement terminé."
