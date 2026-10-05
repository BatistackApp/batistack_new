# Sauvegarde et restauration

## Éléments à sauvegarder

- Base de données complète.
- `storage/app` et les médias utilisateurs.
- Configuration et secrets gérés par l'infrastructure.
- Version applicative et commit déployé.

La méthode de dump dépend du moteur utilisé. La commande doit être adaptée à l'infrastructure et testée hors production avant la RC.

## Validation d'une sauvegarde

Conserver avec chaque sauvegarde :

- date et environnement ;
- version applicative ;
- moteur et version de base ;
- taille et résultat du dump ;
- emplacement de stockage ;
- somme de contrôle si disponible.

## Test de restauration

1. Créer une base vierge.
2. Restaurer le dump.
3. Restaurer les médias dans `storage/app`.
4. Déployer le même commit applicatif.
5. Exécuter `php artisan optimize:clear`.
6. Vérifier connexion, permissions, documents, factures, stocks, RH, paie et comptabilité.
7. Mesurer le temps total de restauration.

Une sauvegarde n'est considérée comme valide qu'après une restauration réussie et documentée.
