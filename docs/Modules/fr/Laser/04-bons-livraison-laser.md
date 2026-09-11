---
title: Les Bons de Livraison Laser
icon: heroicon-o-truck
order: 5
---

# 🚚 Les Bons de Livraison Laser

Le bon de livraison (BL) atteste des quantités réellement remises au client. Dans le cycle laser, il est **l'étape qui déclenche la facturation** : on ne facture que ce qui a été livré.

## 1. Générer un bon de livraison

Depuis une [commande laser](./03-commandes-laser.md), l'action **« Générer un Bon de Livraison »** crée un BL (référence `LBL-…`) reprenant les pièces **restant à livrer** (quantité commandée moins quantité déjà livrée).

## 2. Livrer en une ou plusieurs fois

Une commande peut être livrée partiellement :

- chaque BL ne porte que les quantités effectivement expédiées ;
- la commande cumule les quantités livrées ligne par ligne ;
- tant qu'il reste des quantités à livrer, un nouveau BL peut être généré.

> [!NOTE]
> **Statut de la commande**
> Dès que les quantités livrées couvrent les quantités commandées, la commande passe automatiquement en « Livrée ».

## 3. Réception

Le BL suit le parcours classique **Expédié → Réceptionné**. Une fois réceptionné, les quantités livrées deviennent facturables.

## 4. Vers la facturation

Les quantités livrées et non encore facturées alimentent la génération de la facture. Voir [Factures & Avoirs Laser](./05-factures-avoirs-laser.md).

> [!TIP]
> **Bonne pratique**
> Établissez le bon de livraison dès la remise des pièces : cela garantit que la facture reprenne exactement les quantités livrées, sans écart entre le terrain et la facturation.
