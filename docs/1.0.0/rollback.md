# Procédure de rollback BATISTACK 1.0.0

Un rollback est une opération contrôlée. Revenir au code précédent ne suffit pas nécessairement si une migration a modifié le schéma ou les données.

## 1. Déclenchement

Envisager un rollback en cas de régression bloquante, d'indisponibilité majeure, d'échec de migration ou de corruption suspectée. Le responsable d'exploitation décide du retour arrière et consigne l'incident.

## 2. Geler les traitements

1. Activer le mode maintenance.
2. Suspendre le scheduler ou le cron applicatif.
3. Arrêter les workers via le gestionnaire de processus de l'infrastructure.
4. Empêcher les écritures applicatives pendant le diagnostic.
5. Conserver les logs et identifier les jobs déjà exécutés.

Ne pas supprimer les jobs en attente sans évaluer leurs effets métier.

## 3. Évaluer la compatibilité de la base

Avant de restaurer ou de modifier la base :

- identifier les migrations exécutées pendant le déploiement ;
- vérifier si le code précédent est compatible avec le schéma actuel ;
- déterminer si de nouvelles données ont été écrites après la migration ;
- consulter les méthodes down() des migrations concernées.

Ne pas exécuter migrate:rollback à l'aveugle. Certaines migrations sont irréversibles ou exigent une transformation préalable.

Si le schéma ou les données ne sont pas compatibles avec l'ancien code, la restauration de la sauvegarde pré-déploiement peut être nécessaire. Cette restauration entraîne la perte des écritures effectuées depuis cette sauvegarde : faire valider ce point avant toute action.

## 4. Restaurer le code

Redéployer le tag ou commit précédemment approuvé dans un répertoire de release isolé. Réinstaller les dépendances correspondant à ce commit et reconstruire les assets :

    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    npm ci
    npm run build
    php artisan optimize:clear
    php artisan optimize

Ne pas utiliser git reset --hard sur un répertoire de production contenant des modifications ou des données non sauvegardées.

## 5. Restaurer la base et les fichiers si nécessaire

Suivre [la procédure de sauvegarde et restauration](./backup-restore.md). Restaurer un ensemble cohérent : base, documents et médias associés, et configuration de la version correspondante.

## 6. Remise en service

1. Vérifier php artisan migrate:status et la cohérence des données.
2. Vérifier les permissions, les portails, les factures, les stocks et les documents.
3. Redémarrer les workers.
4. Réactiver le scheduler.
5. Sortir du mode maintenance.
6. Surveiller les logs, les queues et les opérations métier.

Consigner la version restaurée, le commit, la sauvegarde utilisée, les pertes éventuelles, les validations et les actions correctives.
