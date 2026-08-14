---
title: La Gestion des Stocks
icon: heroicon-o-archive-box
order: 4
---

# 📊 La Gestion des Stocks au quotidien

L'état des stocks est visible directement depuis la fiche d'un article ou via la vue globale des entrepôts. Batistack gère le stock avec beaucoup de finesse pour éviter les conflits d'usage de la matière.

## 1. Les 3 niveaux de quantités

Pour chaque article dans un dépôt, vous verrez 3 indicateurs clés :

- **Quantité Physique** : C'est ce qui se trouve réellement dans les rayons de votre dépôt à l'instant T.
- **Quantité Réservée** : C'est la part du stock physique qui est déjà "promise" à un chantier ou à un Ordre de Fabrication (OF), mais qui n'a pas encore quitté l'étagère.
- **Quantité Disponible** : C'est la quantité physique MOINS la quantité réservée. **C'est le seul chiffre qui compte** pour savoir si vous pouvez accepter un nouveau projet !

## 2. Le cycle de vie de la matière (Réservation et Consommation)

Dans Batistack, la sortie de matière s'effectue généralement en deux temps :

1. **La Réservation (Lock)** : Lorsqu'un chantier est planifié, le chef de chantier "Réserve" le matériel. La quantité disponible diminue, mais la quantité physique reste la même (le matériel est toujours là).
2. **La Consommation (Consume)** : Le jour J, les équipes chargent le matériel. On "Consomme" la réservation : la quantité physique diminue et la réservation disparaît. Le matériel est définitivement sorti et son coût est imputé au chantier.

> [!WARNING]
> Si vous supprimez une réservation sans la consommer (bouton "Libérer"), la matière redevient immédiatement *Disponible* pour d'autres projets.

## 3. Seuils d'alerte

Chaque article dans chaque dépôt peut posséder un **Seuil mini**. Si la *Quantité Disponible* tombe en dessous de ce seuil, l'article passe en état d'alerte, vous indiquant qu'un réassort (commande fournisseur ou production) est nécessaire.

## 4. Traçabilité des lots et dates de péremption

Pour les articles sensibles (chimie, EPI, etc.), Batistack offre une traçabilité complète par lot et date de péremption.

- **Numéro de Lot (`batch_number`)** : À chaque entrée en stock d'un article sensible, vous pouvez associer un numéro de lot. Ce numéro suivra l'article tout au long de son cycle de vie dans votre stock.
- **Date de Péremption (`expiration_date`)** : En plus du numéro de lot, vous pouvez spécifier une date de péremption.

### Alertes de Péremption

Batistack surveille automatiquement les dates de péremption des lots en stock. Une alerte est générée pour les lots approchant de leur date d'expiration (par défaut, 30 jours avant), vous permettant de gérer proactivement les stocks et d'éviter les pertes.

> [!NOTE]
> Pour activer la traçabilité pour un article, cochez la case "Article sensible" lors de sa création ou de sa modification.
