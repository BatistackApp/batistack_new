---
title: Ordres de Fabrication & MRP
icon: heroicon-o-clipboard-document-list
order: 2
---

# 📋 Ordres de Fabrication (OF) & MRP

L'Ordre de Fabrication (OF) est le document de référence qui déclenche et suit la production d'un ou plusieurs articles dans votre atelier.

## 1. Création et cycle de vie d'un OF

Un OF peut être créé de deux manières :
- **Manuellement**, depuis l'interface d'administration.
- **Automatiquement**, lorsqu'une Commande Client (issue du module Commerce) est confirmée.

Un OF passe par différents statuts :
*   `Brouillon` -> `Planifié` -> `En cours` -> `Terminé` (ou `Annulé`).

Chaque OF est généré avec un document PDF associé contenant un QR Code. Ce code permet un scan logistique rapide en atelier.

## 2. Le moteur MRP (Calcul des Besoins)

Batistack intègre un moteur **MRP** (Material Requirements Planning). Lors de la création d'un OF, le système analyse la recette du produit (Nomenclature ou BOM). 
Il calcule automatiquement les besoins en matières premières (Composants) nécessaires pour fabriquer la quantité demandée.

> [!NOTE]  
> **Anticipation des ruptures** : Le système génère automatiquement des alertes et prépare des brouillons de **Commandes d'Achat** si une rupture de stock est détectée pour accomplir un OF.

## 3. Déstockage et Intégration Inventaire

Lorsqu'un OF est validé ou consommé :
1.  Les matières premières sont **automatiquement décrémentées** de votre stock réel.
2.  Le produit fini est **crédité** dans le stock.
3.  Le système ajuste le coût de revient (PUMP) en fonction des coûts réels des matières utilisées.

## 4. Traçabilité (Lots et Séries)

Pour répondre aux exigences de traçabilité qualité :
- Vous pouvez (ou devez, selon le paramétrage) renseigner le **numéro de lot** ou le **numéro de série** des composants consommés.
- Le produit fini finalise la chaîne de traçabilité en se voyant lui-même attribuer un lot ou numéro de série.
- Vous disposez également d'un système de **Contrôles Qualité** (`QualityCheck`) permettant à un inspecteur de valider ou refuser une étape de production.
