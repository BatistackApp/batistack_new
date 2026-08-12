---
title: Tickets & Facturation
icon: heroicon-o-ticket
order: 2
---

# 🎫 Gestion des Tickets & Facturation

Le cœur du module SAV réside dans le suivi financier et logistique de chaque intervention. Batistack automatise la transition de la réparation à la facturation.

## 1. Régie ou Forfait ?

Lors de la création d'un ticket SAV, vous devez choisir son type :
- **Forfait** : Le prix est fixe (convenu à l'avance). Le temps passé et les pièces ajoutées impacteront votre marge (rentabilité), mais n'affecteront pas le prix facturé au client final.
- **Régie** : La facturation dépend de la réalité du terrain. Les heures pointées par le technicien et chaque pièce détachée utilisée seront ajoutées à la facture finale.

## 2. Déstockage Automatique

Pour éviter les inventaires fastidieux, le module Interventions est lié au module Articles/Stocks.
Dès qu'une intervention bascule au statut **"Terminée"**, les pièces détachées (et consommables) déclarées par le technicien sont **automatiquement décrémentées** de l'entrepôt (ou du camion du technicien).

## 3. Facturation en un clic

Une fois que le technicien a clôturé l'intervention et fait signer le Bon de Travail au client, le statut passe à Terminé.
Le gestionnaire SAV (depuis son tableau de bord d'administration) peut alors générer une **Facture Client Brouillon** en un seul clic. Le système compile automatiquement le temps passé et le matériel (selon le type Régie ou Forfait).

> [!NOTE]  
> **Suivi de Rentabilité**
> Le Dashboard SAV vous permet d'analyser la variance de rentabilité. Si vous faites beaucoup de SAV "Forfaitaire", surveillez bien que le coût des pièces et du temps passé ne dépasse pas le prix vendu !
