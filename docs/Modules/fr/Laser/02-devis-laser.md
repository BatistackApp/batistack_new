---
title: Les Devis Laser
icon: heroicon-o-document-text
order: 3
---

# 📝 Les Devis Laser

Le devis est le point d'entrée commercial du module. Sa particularité : le **prix se calcule automatiquement** à partir de la géométrie de chaque pièce et du matériau choisi.

## 1. Créer un devis

Depuis **Commercial → Devis**, créez un devis (référence `LAQ-…`), sélectionnez le **client** et, si besoin, une **date d'expiration** et des **conditions particulières**.

## 2. Ajouter les lignes de pièces

Chaque ligne représente une pièce à découper. Renseignez :

- le **matériau** (qui apporte la densité et les tarifs),
- les **dimensions** : longueur, largeur, épaisseur (en mm),
- la **quantité**,
- le **périmètre de découpe** (longueur de trait laser),
- éventuellement un **coût de programmation** (fixe) et une **remise**.

Le système calcule alors automatiquement le **poids** et le **prix unitaire HT** à partir de ces éléments.

> [!NOTE]
> **Le prix suit la matière et la découpe**
> Le coût dépend à la fois de la matière consommée (poids lié aux dimensions × densité) et de la longueur de découpe (périmètre). Plus une pièce est grande ou découpée, plus elle est chère.

## 3. Importer un fichier DXF

Plutôt que de saisir les dimensions à la main, vous pouvez **importer un fichier DXF** sur une ligne de devis. Batistack analyse le tracé et en déduit automatiquement les dimensions et le périmètre de découpe de la pièce.

> [!TIP]
> **Gain de temps**
> L'import DXF évite la ressaisie des cotes depuis un plan : vous gardez la main pour vérifier et ajuster les quantités et le matériau.

## 4. Transformer en commande

Une fois le devis **accepté**, utilisez l'action **« Transformer en commande »** pour créer la commande laser correspondante. Le devis n'est alors plus modifiable et le stock d'étapes se poursuit. Voir [Les Commandes Laser](./03-commandes-laser.md).
