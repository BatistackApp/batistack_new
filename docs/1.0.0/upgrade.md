# Mise à niveau vers BATISTACK 1.0.0 RC

Cette procédure concerne une instance BATISTACK déjà installée. Elle doit être répétée et validée en préproduction avec une copie représentative des données avant toute mise à niveau de production.

## 1. Précontrôles

1. Identifier précisément la version, le tag et le commit actuellement déployés.
2. Identifier le moteur et la version de base de données.
3. Lire [les limitations connues](./known-limitations.md) et [le statut des modules](./module-status.md).
4. Comparer les variables de l'instance avec .env.example sans remplacer le fichier .env existant.
5. Vérifier l'espace disque, les permissions, les queues, le scheduler et les intégrations.
6. Lire les migrations ajoutées depuis la version actuellement installée.
7. Réaliser une sauvegarde complète et effectuer un test de restauration.
8. Préparer le commit précédent, la procédure de rollback et le responsable de décision.

Ne jamais remplacer la configuration de production par .env.example. Ne jamais générer une nouvelle APP_KEY pour une instance existante.

## 2. Préparer le déploiement

Utiliser un tag/commit de release approuvé et vérifié. Ne pas déployer directement une branche mobile.

Mettre l'application en maintenance lorsque nécessaire et suspendre les workers selon la procédure de l'infrastructure. Sauvegarder la base et les fichiers immédiatement avant la bascule.

## 3. Déployer le code et les dépendances

Dans le répertoire de release :

    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    npm ci
    npm run build

Conserver le fichier .env et les données persistantes hors du répertoire remplacé. Vérifier que les assets construits correspondent au commit déployé.

## 4. Migrations et caches

Après revue des migrations et validation de la sauvegarde :

    php artisan migrate --force
    php artisan optimize:clear
    php artisan optimize

Ne jamais utiliser migrate:fresh, migrate:refresh ou db:wipe sur une base contenant des données à conserver.

Ne pas exécuter aveuglément php artisan migrate:rollback en cas d'incident : certaines migrations ne peuvent pas être annulées sans perte ou transformation de données. Consulter le code de chaque migration et la procédure [rollback](./rollback.md).

## 5. Contrôles après mise à niveau

    php artisan about
    php artisan migrate:status
    php artisan schedule:list

Vérifier l'authentification, les rôles, permissions et l'isolation des portails ; les données métier ; les documents privés et médias ; PDF, mails et queues ; tâches planifiées et intégrations activées ; logs applicatifs et jobs échoués.

Sortir du mode maintenance uniquement après validation des contrôles. Consigner le commit, les migrations et les résultats.
