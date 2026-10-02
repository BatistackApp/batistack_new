# Déploiement de la release candidate

## Préparation

- Tous les checks CI sont verts.
- La checklist GO / NO-GO est à jour.
- La sauvegarde et la restauration ont été testées.
- Le rollback est documenté et testé.
- Les variables externes sont disponibles dans l'environnement cible.

## Services à prévoir

- Scheduler Laravel exécuté chaque minute.
- Worker de queue avec une stratégie de redémarrage.
- Stockage persistant pour les médias et documents.
- Chromium pour Browsershot.
- Accès mail, signature, banque et paiement selon le périmètre activé.

Le scheduler doit notamment traiter les commandes RH et Locations configurées dans `routes/console.php`.

## Après déploiement

```bash
php artisan migrate:status
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

Vérifier les logs, les erreurs Sentry si activé, les jobs échoués, les PDF, les mails, les portails et une opération métier représentative.
