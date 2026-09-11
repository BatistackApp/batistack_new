---
title: Factures & Avoirs Laser
icon: heroicon-o-receipt-percent
order: 5
---

# 🧾 Factures & Avoirs Laser

Ce volet couvre la **facturation** des pièces livrées et la gestion des **avoirs** en cas de correction.

## 1. Générer une facture

Depuis une [commande laser](./03-commandes-laser.md), l'action **« Générer une Facture »** crée une facture (référence `LFAC-…`) pour **toutes les lignes livrées non encore facturées**.

- Seules les quantités livrées et pas encore facturées sont reprises.
- Le total est calculé avec le taux de TVA du module.
- Une **date d'échéance** est proposée automatiquement pour le règlement.

> [!IMPORTANT]
> **Facturation partielle possible**
> Si vous livrez une commande en plusieurs fois, vous pouvez générer une facture à chaque livraison. Chaque pièce n'est facturée **qu'une seule fois** : le système suit les quantités déjà facturées pour éviter tout doublon.

## 2. Valider (légaliser) une facture

Tant qu'elle est en **brouillon**, une facture reste modifiable et supprimable. La **validation** rend la facture définitive :

- elle bascule la commande en « Facturée » dès que tout le livré est facturé ;
- la facture ne peut plus être modifiée ni supprimée.

> [!WARNING]
> **Validation irréversible**
> Une fois validée, une facture ne peut plus être modifiée. Pour corriger une facture validée, il faut émettre un **avoir**.

## 3. Supprimer une facture brouillon

Seule une facture **en brouillon** peut être supprimée. La suppression **restibue** automatiquement les quantités facturées sur la commande, qui redevient facturable.

## 4. Créer un avoir

Sur une facture validée ou payée, l'action **« Créer un avoir »** génère un avoir (référence `LAVO-…`) :

- vous choisissez le **motif** et, éventuellement, un **montant partiel** (sinon l'avoir porte sur le solde restant) ;
- le montant déjà crédité est décompté du solde de la facture d'origine ;
- **impossible de dépasser le montant total** de la facture : le système bloque tout avoir supérieur au solde restant.

> [!NOTE]
> **Avoirs partiels et cumulés**
> Plusieurs avoirs peuvent être émis successivement sur une même facture, tant que leur total ne dépasse pas le montant facturé. Une fois la facture intégralement créditée, il n'est plus possible d'émettre un nouvel avoir.

## 5. Imprimer les documents

Factures et avoirs disposent chacun d'un bouton **« Imprimer PDF »** pour télécharger le document de synthèse correspondant.
