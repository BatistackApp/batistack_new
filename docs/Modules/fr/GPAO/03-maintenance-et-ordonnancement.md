---
title: Maintenance & Ordonnancement
icon: heroicon-o-calendar-days
order: 4
---

# 📅 Maintenance & Ordonnancement (APS)

Batistack propose des fonctionnalités avancées pour maximiser la disponibilité de votre parc machines et optimiser le planning de production grâce à l'intelligence algorithmique.

## 1. Ordonnancement Automatique IA (APS)

La planification manuelle d'une dizaine d'Ordres de Fabrication peut devenir un casse-tête. Le système **APS (Advanced Planning and Scheduling)** de Batistack s'en charge pour vous.

Depuis la vue Kanban, le bouton **"Planification Automatique"** déclenche un algorithme qui :
1. Analyse tous les OF au statut `Planifié`.
2. Les trie en priorité absolue selon la date de livraison exigée par le client.
3. Évalue la disponibilité des matières premières : l'algorithme "écarte" et met en attente les OF pour lesquels le stock est insuffisant.
4. Assigne l'ordre optimal pour chaque machine.

## 2. La mini-GMAO : Entretien du Parc Machine

Un parc bien entretenu est la clé de la productivité. Batistack intègre une mini-GMAO (Gestion de Maintenance Assistée par Ordinateur) :

- **Inventaire des Machines** : Chaque machine possède sa fiche technique.
- **Maintenance Préventive** : À chaque fois qu'un opérateur termine un OF sur une machine, le système calcule le temps d'utilisation (heures de rotation). Si ce temps dépasse le seuil constructeur configuré, un **Ticket d'Intervention** est automatiquement généré pour alerter l'équipe maintenance.
- **Maintenance Curative** : En cas de casse, les opérateurs peuvent ouvrir un ticket d'incident depuis leur portail tactile pour bloquer l'assignation de nouveaux OF sur cette machine.

## 3. Gestion des Rebuts (Scrap Management)

Durant la production, il arrive que des matières soient endommagées ou qu'une pièce usinée soit défectueuse. La gestion des **Rebuts** permet de déclarer ces pertes de manière structurée.

Lors de la déclaration d'un rebut :
- Vous précisez le motif (Casse, Défaut matière, Erreur de réglage).
- Le stock est définitivement ajusté à la baisse pour refléter la réalité.
- Les rapports KPI mettent en évidence le **Taux de Rebut Global**, permettant à la direction de cibler les problèmes de formation ou de qualité matière.
