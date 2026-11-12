# Déploiement BATISTACK 1.0.0 RC

Ce document décrit les contrôles et opérations à réaliser sur le serveur. Il ne constitue pas une autorisation de déployer en production : la décision GO/NO-GO doit être prise séparément.

## 1. Distinguer release GitHub et déploiement serveur

Le workflow .github/workflows/deploy-production.yml se déclenche à la fermeture d'une PR vers production, uniquement si celle-ci a été fusionnée. D'après son contenu actuel, il calcule un tag Semantic Versioning et crée une GitHub Release.

**Ce workflow ne déploie pas le code sur le serveur.** La création d'une GitHub Release ne prouve donc pas qu'un déploiement a été effectué.

Le script deploy.sh présent dans le dépôt est actuellement un script manuel non transactionnel. Il contient notamment git reset --hard, git pull, npm install et chmod -R 777 storage/. Il ne réalise pas de sauvegarde préalable ni de rollback automatique. **Ne pas l'utiliser tel quel en production sans audit et validation de ses risques.**

## 2. Préparation obligatoire

Avant toute intervention :

- identifier le tag, le commit et la version actuellement en production ;
- confirmer le tag/commit cible et l'approbation de release ;
- vérifier l'état de la CI sur le commit exact destiné au déploiement ;
- vérifier les variables d'environnement et les secrets requis ;
- contrôler l'espace disque, la base, les queues et les services externes ;
- réaliser une sauvegarde complète et vérifier qu'elle est restaurable ;
- définir une fenêtre d'intervention, un responsable et un plan de retour arrière ;
- prévenir les utilisateurs si une indisponibilité est prévue.

Ne pas déployer depuis develop, master ou une branche de travail. Utiliser exclusivement le tag/commit explicitement approuvé.

## 3. Services d'exploitation

### Scheduler

Le dépôt déclare de nombreuses tâches planifiées dans routes/console.php, certaines à des horaires définis dans le fuseau Europe/Paris. Le cron système doit appeler Laravel chaque minute :

    * * * * * cd /CHEMIN/ABSOLU/DE/L'APPLICATION && php artisan schedule:run >> /dev/null 2>&1

Remplacer le chemin par celui de l'installation et exécuter la commande avec l'utilisateur système de l'application. Vérifier le fuseau horaire du serveur et les sorties de php artisan schedule:list.

Le dépôt déclare également queue:work --stop-when-empty dans le scheduler. La stratégie de workers doit être cohérente avec QUEUE_CONNECTION et l'infrastructure ; ne pas ajouter un second worker permanent sans vérifier le comportement attendu et éviter les traitements en double.

### Queues

Le backend de queue dépend de QUEUE_CONNECTION. Le fichier d'exemple utilise database. Vérifier que le backend retenu est configuré, que les jobs sont consommés, et surveiller les jobs échoués. Après mise à jour du code, php artisan queue:restart demande aux workers Laravel de redémarrer après leur job courant ; cela ne remplace pas la supervision du processus.

### Stockage et PDF

Le disque local privé pointe vers storage/app/private, le disque public vers storage/app/public. Ces données doivent être persistantes et sauvegardées. Les fonctions PDF via Browsershot nécessitent Node.js et Chromium accessibles au processus PHP ; renseigner les chemins BROWSERSHOT_* si nécessaire.

### Intégrations

Vérifier uniquement les intégrations activées dans l'instance : mail, DocuSeal, Bridge, Stripe, API Entreprise, Google Vision, Digiposte, météo et autres fournisseurs. Les tests avec mocks ne prouvent pas la disponibilité réelle d'un fournisseur.

## 4. Séquence de déploiement contrôlée

La séquence exacte doit être adaptée à l'architecture du serveur et validée en préproduction. À titre de trame :

1. Mettre l'application en maintenance si la migration ou la bascule l'exige.
2. Suspendre les traitements asynchrones selon la procédure de supervision.
3. Sauvegarder base et fichiers, puis vérifier les artefacts.
4. Déployer le tag/commit approuvé dans un répertoire de release isolé.
5. Installer les dépendances PHP de production :

       composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

6. Installer les dépendances front depuis le lockfile et construire les assets :

       npm ci
       npm run build

7. Vérifier la configuration et les permissions du compte applicatif.
8. Exécuter les migrations approuvées :

       php artisan migrate --force

9. Reconstruire les caches :

       php artisan optimize:clear
       php artisan optimize

10. Redémarrer les workers et services selon leur gestionnaire de processus.
11. Sortir du mode maintenance.
12. Effectuer les tests de fumée et surveiller les logs.

Ne pas lancer une migration non relue ou destructive en production. Une migration exécutée n'est pas automatiquement réversible.

## 5. Vérifications post-déploiement

- php artisan about et php artisan migrate:status ;
- connexion et permissions des profils concernés ;
- consultation des tiers, chantiers, commandes et factures ;
- génération et téléchargement d'un document privé ;
- génération PDF et envoi d'un mail ;
- création puis traitement d'un job ;
- exécution du scheduler ;
- absence de jobs échoués et d'erreurs bloquantes dans les logs ;
- vérification des intégrations activées.

Documenter le tag, le commit, l'heure, l'opérateur, les migrations exécutées, les résultats des tests de fumée et toute anomalie.
