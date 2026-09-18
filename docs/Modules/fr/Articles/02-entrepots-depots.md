---
title: Les Entrepôts
icon: heroicon-o-building-storefront
order: 3
---

# 🏭 Les Entrepôts (Dépôts)

Un entrepôt (ou dépôt) dans Batistack représente n'importe quel emplacement physique capable d'héberger du stock. 

La force du système réside dans le fait qu'un "entrepôt" ne désigne pas obligatoirement un immense hangar. 

## Exemples d'Entrepôts

Vous pouvez modéliser votre logistique comme bon vous semble en créant des entrepôts tels que :
- **Le Magasin Principal** : Votre atelier ou dépôt de base.
- **Les Véhicules Utilitaires** : (ex: "Camion 1", "Fourgonnette Jean") pour tracer le matériel embarqué par les techniciens.
- **Les Zones de Quarantaine** : Pour isoler des marchandises défectueuses.

## Organisation de l'espace

Au sein du logiciel, vous attribuez chaque article en stock à un ou plusieurs entrepôts. Un même article "Câble électrique 3G1.5" peut très bien se trouver à la fois dans le Magasin Principal (pour 500m) et dans le Camion 1 (pour 50m).

## Emplacements de stockage (bin-picking)

Pour aller plus loin dans l'organisation physique d'un grand dépôt, vous pouvez définir des **emplacements** à l'intérieur d'un entrepôt (ex : Allée A / Travée 3 / Bac 2). Chaque quantité de stock peut alors être localisée précisément sur une étagère.

- Le magasinier sait **où trouver** une référence sans parcourir tout le dépôt.
- Les mouvements et les inventaires peuvent être suivis **par emplacement**, ce qui fiabilise le comptage.

> [!NOTE]
> **Optionnel**
> L'organisation par emplacement est progressive : vous pouvez très bien démarrer avec un simple entrepôt global, puis détailler les emplacements de vos grands magasins uniquement.

> [!TIP]
> Multiplier les entrepôts (ex: un entrepôt par camion) offre une traçabilité redoutable, mais exige plus de rigueur de la part des magasiniers (il faudra saisir un mouvement de stock chaque fois qu'un outil passe du magasin au camion). Si vous débutez avec Batistack, commencez avec un seul "Magasin Principal".
