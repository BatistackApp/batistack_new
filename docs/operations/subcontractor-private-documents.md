# Migration des documents de sous-traitants vers le stockage privé

## Objectif et précautions

La commande `subcontractors:secure-documents` remplace les références aux documents de sous-traitants stockés sur les disques `public` ou `s3-public` par des références vers le disque privé `local`.

**La commande conserve volontairement les fichiers sources publics.** Elle vérifie la copie avec SHA-256 et ne supprime jamais automatiquement les sources. Ne supprimez pas manuellement les fichiers publics tant que chaque copie n'a pas été vérifiée.

Prévoir une fenêtre de maintenance, une sauvegarde de la base de données et des fichiers concernés, ainsi qu'un accès permettant de restaurer ces sauvegardes.

## Procédure recommandée

1. Déployer le code validé sur un environnement de préproduction représentatif.
2. Sauvegarder la base de données et les fichiers des disques `public` et `s3-public`.
3. Exécuter la simulation et conserver sa sortie :

   ```bash
   php artisan subcontractors:secure-documents --dry-run
   ```

4. Examiner le nombre de documents et de médias annoncés. Vérifier que le disque `local` est configuré comme stockage privé et que l'espace disponible est suffisant.
5. Exécuter la migration :

   ```bash
   php artisan subcontractors:secure-documents
   ```

6. Contrôler le code de sortie et le récapitulatif. Toute erreur doit être investiguée avant de poursuivre.
7. Vérifier un échantillon représentatif de documents générés et de factures : accès autorisé pour le sous-traitant propriétaire, refus d'accès pour une autre entreprise, ouverture/téléchargement corrects et présence du fichier sur le disque privé.
8. Comparer les références de la base avec les fichiers privés et vérifier les journaux applicatifs. Conserver les sauvegardes et les sources publiques jusqu'à la fin de la validation.
9. Faire valider explicitement le nettoyage des sources par la personne responsable de l'exploitation. Ce nettoyage est une opération séparée, à effectuer uniquement après confirmation des copies et des accès privés.

## Limites connues

- Le mode `--dry-run` liste les éléments ciblés ; il ne copie pas les fichiers et ne vérifie pas les empreintes.
- Pendant l'exécution, la commande compare les empreintes SHA-256 source/destination. Si la destination existe déjà et correspond exactement à la source, elle est considérée comme une copie réussie, ce qui permet de reprendre une migration interrompue. Si les empreintes diffèrent, la commande échoue pour cet élément sans écraser la destination.
- En cas d'échec ou de copie partielle, la source reste conservée. Examinez le message d'erreur et restaurez depuis la sauvegarde si nécessaire.
- La vérification d'intégrité réduit le risque de copie incomplète, mais les contrôles fonctionnels des accès et des téléchargements restent obligatoires.
