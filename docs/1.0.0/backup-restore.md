# Sauvegarde et restauration BATISTACK 1.0.0

Une sauvegarde n'est considérée comme valide qu'après un test de restauration réussi. Définir une politique de rétention, un emplacement séparé du serveur applicatif et des contrôles d'accès adaptés à la sensibilité des données.

## 1. Périmètre à sauvegarder

Sauvegarder dans un même point de cohérence :

- la base de données complète ;
- les fichiers utilisateurs et documents, notamment storage/app (disques local privé et public) ;
- les éventuels stockages externes (bucket S3 ou équivalent) selon leur politique de versioning/rétention ;
- la configuration d'exploitation et les secrets, de manière chiffrée et hors dépôt ;
- le tag/commit applicatif et les versions PHP, Composer, Node, moteur de base et extensions ;
- les éléments nécessaires à la restauration des services associés.

Les caches et fichiers temporaires peuvent être reconstruits ; ils ne remplacent pas les documents métier persistants.

## 2. Avant la sauvegarde

- Identifier l'environnement, le moteur de base et la version.
- Définir si la sauvegarde doit être cohérente à chaud ou si une fenêtre de maintenance est nécessaire.
- Suspendre les écritures ou utiliser les mécanismes de snapshot cohérents du moteur.
- Vérifier l'espace disponible et les droits d'accès.
- Ne jamais écrire les mots de passe ou secrets en clair dans les journaux.

## 3. Exemple de sauvegarde MySQL / MariaDB

Adapter les paramètres à l'infrastructure. Utiliser un compte de sauvegarde dédié et protéger le fichier de sortie :

    mysqldump --single-transaction --routines --triggers -h DB_HOST -u DB_USER -p DB_NAME > batistack.sql

Pour restaurer dans une base préparée :

    mysql -h DB_HOST -u DB_USER -p DB_NAME < batistack.sql

Pour PostgreSQL, utiliser pg_dump et pg_restore avec les options adaptées à la version et à la stratégie de sauvegarde. Pour SQLite, arrêter les écritures et utiliser l'outil de sauvegarde SQLite plutôt qu'une simple copie d'un fichier actif.

Ces exemples sont des modèles : vérifier les options, privilèges, encodages, objets et contraintes du moteur réellement utilisé.

## 4. Sauvegarde des fichiers

Archiver les fichiers persistants de storage/app en conservant les permissions et l'arborescence. Si un stockage S3 ou distant est configuré, vérifier séparément son versioning, sa réplication et la restauration d'objets supprimés.

Ne pas considérer php artisan storage:link comme une sauvegarde : cette commande crée seulement le lien symbolique public.

## 5. Métadonnées et intégrité

Associer à chaque sauvegarde : date/heure et environnement ; tag et commit applicatifs ; moteur et version de base ; périmètre (base, fichiers, buckets, configuration) ; taille, durée et résultat de chaque export ; emplacement, chiffrement et durée de rétention ; somme de contrôle SHA-256 lorsque possible.

Exemple de contrôle d'intégrité d'une archive :

    sha256sum batistack.sql

Conserver la somme de contrôle dans un emplacement séparé de l'archive.

## 6. Test de restauration

1. Préparer un environnement isolé et une base vide.
2. Restaurer le dump avec le moteur correspondant.
3. Restaurer les fichiers et médias au même emplacement logique.
4. Déployer le même tag/commit que celui associé à la sauvegarde.
5. Restaurer la configuration sans exposer les secrets.
6. Exécuter php artisan optimize:clear.
7. Vérifier la connexion, les permissions, les documents, factures, stocks, RH, paie et comptabilité.
8. Vérifier les queues et les tâches planifiées sans déclencher d'envoi réel ou de paiement externe.
9. Mesurer le temps de restauration et documenter les écarts.

Ne jamais tester une restauration directement sur la base de production.
