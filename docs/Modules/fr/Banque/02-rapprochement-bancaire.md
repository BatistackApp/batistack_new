---
title: Le Rapprochement Bancaire (Lettrage)
icon: heroicon-o-link
order: 3
---

# 🔗 Le Rapprochement Bancaire (Lettrage)

Le lettrage est l'action de lier une ligne de votre relevé bancaire à un document justificatif (une facture client, une facture fournisseur, une fiche de paie).

C'est ce qui permet de dire : "Ces 1200€ reçus aujourd'hui correspondent au paiement de la facture F-2026-045".

## 1. Lettrage Automatique par l'Intelligence Artificielle

Batistack embarque un **Moteur de Suggestion Intelligent**. 
Chaque nuit, lors de la récupération des nouvelles transactions, l'ERP analyse :
1. Le montant de la transaction.
2. Le nom de l'émetteur (ex: "Virement M. Dupont").
3. La référence saisie par le client (ex: "Facture 045").

Le moteur attribue un "score de pertinence". S'il est sûr à 100% que le virement de 1200€ de M. Dupont correspond à la facture F-2026-045 de 1200€, **le lettrage est effectué automatiquement**. 

> [!IMPORTANT]
> **Conséquence magique** : Dès qu'une transaction est lettrée avec une Facture, le statut de cette facture passe instantanément à "PAID" (Payée). Vous n'avez plus à pointer manuellement vos comptes !

## 2. Lettrage Manuel

Si le moteur a un doute (ex: un client vous a payé deux factures d'un coup avec un seul virement), la transaction restera "À traiter".

1. Allez dans **Banque > Transactions**.
2. Sur la ligne bancaire, cliquez sur "Lettrer".
3. L'interface vous proposera les factures dont le montant s'approche le plus, ou vous permettra de chercher manuellement les factures correspondantes.
4. Vous pouvez lier une transaction à *plusieurs* factures.

## 3. Catégorisation des Dépenses

Toutes les transactions ne correspondent pas à des factures de l'ERP (ex: frais bancaires, péages, repas).
Pour ces lignes, le moteur de catégorisation attribue automatiquement une "Catégorie" (ex: "Frais de déplacement", "Frais financiers") en se basant sur le libellé de l'opération. Cela alimente vos tableaux de bord de suivi des dépenses.
