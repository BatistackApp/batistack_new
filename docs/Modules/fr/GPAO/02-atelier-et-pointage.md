---
title: Atelier & Pointage Opérateur
icon: heroicon-o-users
order: 3
---

# 👷 Atelier et Pointage Opérateur

Le module GPAO propose des interfaces distinctes selon le profil de l'utilisateur : des outils d'administration pour les chefs d'atelier, et un portail simplifié pour les opérateurs sur machines.

## 1. Vues d'Administration et Chef d'Atelier

Pour piloter efficacement la production, Batistack met à disposition des vues visuelles et interactives :

- **Kanban de Production** : Une interface fluide (Drag & Drop) permettant de déplacer les OF d'un statut à l'autre d'un simple glisser-déposer.
- **Calendrier Capacitaire** : Une vue chronologique permettant d'étaler la charge de travail et d'identifier les goulots d'étranglement ou les périodes creuses de l'atelier.
- **Dashboard KPI** : Un tableau de bord décisionnel dédié à la production qui affiche le Taux de Rendement Synthétique (TRS), le tunnel de fabrication, et qui met en évidence les OF bloqués ou en rupture.

## 2. Interface Opérateur (Portail Salarié)

L'opérateur devant sa machine n'a pas besoin de l'interface complexe d'administration. Batistack propose une vue **Portail Salarié** optimisée pour les tablettes (Tactile).

Depuis ce portail, l'opérateur peut :
1.  Scanner un OF (via le QR Code du document papier) ou le sélectionner dans sa liste des tâches.
2.  Pointer son temps de travail en temps réel grâce aux boutons interactifs : **"Démarrer"**, **"Pause"**, **"Terminer"**.
3.  Déclarer les rebuts ou signaler un problème sur une machine.

> [!TIP]  
> **Valorisation immédiate** : Dès que l'opérateur clique sur "Terminer", son temps de travail (Main d'Œuvre Directe - MOD) est enregistré, imputé au coût de l'OF pour analyser la rentabilité, et synchronisé dans le module RH pour la préparation de sa fiche de paie.

## 3. Alertes Temps Réel (WebPush)

Grâce à l'intégration des technologies PWA (Progressive Web App), l'ERP peut envoyer des notifications push directement sur les terminaux des opérateurs, même s'ils naviguent sur un autre écran. 
Cela est particulièrement utile pour signaler une urgence (ex: "Stoppez la production de l'OF #123, modification client").
