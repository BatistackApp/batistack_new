---
title: Les Matériaux Laser
icon: heroicon-o-square-3-stack-3d
order: 2
---

# 🧱 Les Matériaux Laser

Le **référentiel matériaux** est la base de tout votre chiffrage. Chaque matériau décrit une matière première que votre machine peut découper (acier, inox, aluminium, etc.). Une fois renseigné, il alimente automatiquement le calcul de prix des devis.

## 1. Créer un matériau

Depuis le menu **Référentiel → Matériaux**, créez une fiche et renseignez les informations clés :

- **Nom / Désignation** : ex. « Acier S235 », « Inox 304 ».
- **Densité (kg/m³)** : indispensable pour calculer le poids de chaque pièce.
- **Prix au kilo** et/ou **prix au mètre de découpe** : vos tarifs de référence.
- **Épaisseur maximale** : l'épaisseur au-delà de laquelle la machine ne peut pas découper ce matériau.

> [!IMPORTANT]
> **La densité pilote le poids, donc le prix.**
> Une densité erronée fausse automatiquement le poids et le montant de tous les devis qui utilisent ce matériau. Vérifiez-la avec attention.

## 2. Activer ou désactiver un matériau

Un matériau peut être rendu **inactif** (par exemple une matière que vous ne découpez plus) sans être supprimé : il disparaît des sélections de devis, mais l'historique des documents existants est conservé.

## 3. Lien avec le chiffrage

Lors de la saisie d'une ligne de devis, le matériau choisi apporte automatiquement sa densité et ses tarifs. Vous n'avez plus qu'à saisir les dimensions de la pièce : le poids et le prix se calculent seuls. Voir [Les Devis Laser](./02-devis-laser.md).

> [!TIP]
> **Bien démarrer**
> Créez d'abord l'ensemble de vos matériaux avant de saisir des devis : c'est ce référentiel qui garantit un chiffrage cohérent et rapide.
