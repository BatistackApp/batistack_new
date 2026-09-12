---
title: Les Commandes Laser
icon: heroicon-o-clipboard-document-list
order: 4
---

# 📋 Les Commandes Laser

La commande laser naît d'un **devis accepté**. Elle porte l'ordre de fabrication des pièces et pilote la suite du cycle : livraison puis facturation.

## 1. Cycle de vie d'une commande

Une commande (référence `LAC-…`) traverse les étapes suivantes :

1. **Brouillon / Confirmée** — après transformation du devis.
2. **En cours** — mise en production.
3. **Livrée** — lorsque les quantités livrées couvrent les quantités commandées.
4. **Facturée** — lorsque toutes les quantités livrées ont été facturées.

> [!NOTE]
> **Passage automatique**
> Vous n'avez pas à changer le statut à la main : il évolue automatiquement au fil des bons de livraison et des factures rattachées à la commande.

## 2. Les lignes de commande

La commande reprend les pièces du devis (matériau, dimensions, quantités). Chaque ligne suit deux compteurs : la **quantité livrée** et la **quantité déjà facturée**, qui vous indiquent à tout moment ce qu'il reste à traiter.

## 3. Les actions disponibles

Depuis la fiche d'une commande, vous disposez de boutons d'action :

- **« Confirmer »** pour valider la commande.
- **« Générer un Bon de Livraison »** pour livrer tout ou partie des pièces. Voir [Les Bons de Livraison Laser](./04-bons-livraison-laser.md).
- **« Générer une Facture »** pour facturer les pièces livrées non encore facturées. Voir [Factures & Avoirs Laser](./05-factures-avoirs-laser.md).
- **« Annuler »** pour fermer une commande sans suite.

> [!TIP]
> **Livrer puis facturer**
> On ne facture que ce qui a été **livré**. Pensez donc à établir le bon de livraison avant de générer la facture correspondante.
