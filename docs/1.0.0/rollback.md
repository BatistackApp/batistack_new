# Procédure de rollback

## Déclenchement

Déclencher un rollback en cas de bug bloquant, corruption de données, migration échouée ou indisponibilité majeure.

## Retour du code

1. Suspendre les queues et les tâches planifiées.
2. Activer la maintenance.
3. Revenir au commit stable précédent.
4. Réinstaller les dépendances et reconstruire les assets.
5. Nettoyer et reconstruire les caches.

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan optimize
```

## Base de données

Ne pas annuler manuellement une migration déjà exécutée sans procédure validée. Restaurer la base uniquement si les données ont été modifiées de manière incompatible, en utilisant la sauvegarde validée avant déploiement.

## Vérification

- désactiver la maintenance ;
- redémarrer les workers ;
- vérifier les logs ;
- tester connexion, facturation, stocks, portails et queues ;
- documenter l'incident et le commit restauré.
