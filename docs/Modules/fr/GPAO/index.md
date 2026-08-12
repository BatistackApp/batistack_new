---
title: Module GPAO
icon: heroicon-o-wrench-screwdriver
order: 1
---

# 🏭 Module GPAO (Production & Atelier)

Bienvenue dans la documentation du module **GPAO** (Gestion de Production Assistée par Ordinateur) de Batistack.

Ce module est le cœur de votre activité de fabrication. Il vous permet de transformer vos matières premières en produits finis tout en maîtrisant vos coûts, vos délais et la qualité. Il fait le pont entre les commandes commerciales et le suivi logistique de votre inventaire.

## 📑 Que trouverez-vous dans ce module ?

Le module GPAO couvre l'ensemble du cycle de vie de la fabrication :

- [Ordres de Fabrication & MRP](./01-ordres-de-fabrication.md) : Gestion de vos recettes (BOM), génération des besoins, traçabilité (lots/séries) et suivi Qualité.
- [Atelier & Pointage Opérateur](./02-atelier-et-pointage.md) : Les interfaces dédiées à vos équipes sur le terrain (Kanban, Calendrier) et l'application tablette de pointage en temps réel.
- [Maintenance & Ordonnancement](./03-maintenance-et-ordonnancement.md) : La planification intelligente (IA APS), le suivi de votre parc machines (mini-GMAO) et la gestion des rebuts (Scrap).

> [!TIP]
> **Une intégration native**
> La GPAO de Batistack ne fonctionne pas en silo. Lorsqu'un Ordre de Fabrication est consommé, les matières premières sont automatiquement décrémentées de vos stocks (Module Articles) et le coût de revient (PUMP) est ajusté. De plus, les heures pointées par les opérateurs alimentent directement la préparation de la paie (Module RH).
