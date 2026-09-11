---
title: Plan comptable & écritures
icon: heroicon-o-list-bullet
order: 2
---

# 🧮 Plan comptable & écritures

## 1. Le plan comptable

Batistack embarque un **plan comptable général (PCG)** français d'environ 120 comptes, répartis sur les 8 classes (immobilisations, stocks, tiers, financier, charges, produits, résultat). Chaque compte porte un numéro, un libellé et une classe.

> [!NOTE]
> **Base de référence**
> Ce plan sert à classer automatiquement les écritures générées depuis la banque. Vous pouvez l'administrer pour l'adapter à votre entreprise.

## 2. Les écritures comptables

Une écriture comptable enregistre un mouvement : une **date**, un **journal** (achats, ventes, banque, caisse, OD, analytique), un **numéro de pièce**, un **compte**, un **libellé**, et un montant au **débit** ou au **crédit**.

> [!IMPORTANT]
> **Un seul côté par ligne**
> Une écriture ne peut pas être débitée et créditée à la fois : chaque ligne porte soit un débit, soit un crédit. Les écritures sont enregistrées par **paires équilibrées** (débit = crédit).

## 3. Génération automatique depuis la banque

Les écritures naissent principalement du **lettrage bancaire** :

- quand vous lettez (rapprochez) une transaction bancaire, Batistack crée automatiquement la paire d'écritures équilibrée ;
- le compte utilisé dépend du **type de la transaction** (salaires, fournisseurs, clients…), via un mapping catégories → comptes ;
- si vous annulez le lettrage, les écritures liées sont supprimées.

> [!TIP]
> **Lettrage = comptabilité à jour**
> En gardant vos rapprochements bancaires à jour, votre comptabilité l'est automatiquement, sans ressaisie.

## 4. Lettrage et suivi

Chaque écriture porte un **état de lettrage** (non lettrée, partiellement lettrée, lettrée), qui permet de suivre ce qui a été rapproché et ce qui reste ouvert.
