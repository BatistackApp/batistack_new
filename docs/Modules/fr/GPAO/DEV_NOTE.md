---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🏭 Module GPAO (Gestion de Production Assistée par Ordinateur)

## 📌 Vue d'ensemble du Module
Le module **GPAO** a pour objectif de gérer les opérations de production ou d'assemblage en atelier au sein de Batistack. Il s'intègre profondément avec l'inventaire (déstockage des matières premières et entrée des produits finis) et les ressources humaines (pointage de la main d'œuvre).

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Gpao` & `app/Enums/Gpao`)
*   **`ManufacturingOrder` (Ordres de Fabrication - OF)** : Cœur de la production. Supporte la hiérarchie parent/enfant (Sous-OF) et est lié à la commande d'origine du module Commerce.
*   **`ManufacturingRequirement`** : Gestion des nomenclatures et besoins matériels pour chaque OF.
*   **`QualityCheck`** : Traçabilité des inspections qualité (Inspecteur, Date, Validé/Refusé).
*   **`ManufacturingStatus`** : Enum des différents statuts de production.

### 2. Logique Métier & Services (`app/Services/Gpao`)
*   **Service MRP (`MrpService`)** : Moteur de calcul des besoins générant automatiquement les nomenclatures requises pour un OF selon la recette (BOM). Implémente le job `GeneratePurchaseOrdersForShortagesJob` qui calcule les ruptures de stock générées par la production et prépare automatiquement des brouillons de Commandes d'Achat.
*   **Intégration d'Inventaire (`ProductionInventoryService`)** : Consommation des matières premières (décrémentation des stocks) et réception des produits finis avec ajustement automatique du PUMP.
*   **Documentation (`GpaoDocumentService`)** : Génération de documents PDF avec QR codes (via le package `chillerlan/php-qrcode`) attachés aux OFs, facilitant le scan logistique.

### 3. Observers & Événements (`app/Observers/Gpao`)
*   **`ManufacturingOrderObserver`** : Déclenche les actions MRP à la création et gère le cycle de vie de l'OF (ex: génération automatique d'OF lorsqu'une commande client est confirmée). Il calcule et met également à jour les heures d'utilisation (`usage_hours`) de la machine assignée lorsqu'un OF se termine, et déclenche la création d'un ticket de maintenance préventive si le seuil est dépassé.

### 4. Nouveautés Récentes (Issues 229, 230, 231, 232)
*   **Traçabilité** : Ajout des champs de `batch_number` et `serial_number` sur les composants consommés (`ManufacturingRequirement`) et les produits finis (`ManufacturingOrder`).
*   **Gestion des Rebuts (Scrap)** : Nouvelle déclaration de perte intégrée sur l'OF (`ManufacturingScrapService`). Ajustement auto du stock, motif, et affichage du Taux de Rebut Global via un widget Filament.
*   **mini-GMAO** : Intégration des `Machine` et de leurs tickets d'intervention (`MachineMaintenanceTicket`). Assignation des OFs aux machines pour anticiper l'entretien selon la durée d'utilisation.
*   **APS (Ordonnancement IA)** : Implémentation du service `ApsSchedulingService` qui planifie automatiquement les OF au statut `ManufacturingStatus::PLANNED` sur les machines. Il les trie par `customerOrder->delivery_date` (sans garantie de respect des délais planifiés), puis écarte ceux dont le stock est insuffisant. Un bouton a été ajouté au Kanban GPAO.

### 4. Interface Utilisateur (Filament)
*   **Espace Dédié (Panel Switch)** : Création d'un panneau Filament spécifique "Atelier & Production" (`GpaoPanelProvider`).
*   **Vues d'Administration** : 
    *   `ManufacturingOrderResource` pour la gestion classique.
    *   **Vue Kanban Sur-Mesure** (Alpine.js / Livewire) avec Drag & Drop fonctionnel pour visualiser les OFs.
    *   **Calendrier Capacitaire** (FullCalendar) pour gérer le planning interactif de la production.
    *   **Dashboard KPI Production Avancé** : Intégration de tableaux de bord responsifs basés sur `laboiteacode/filament-dashboard-widgets` avec indicateurs clés (TRS Global et Objectif de Qualité), le Tunnel de Fabrication pour les OFs, et une gestion ciblée des Ruptures de Stocks et bloquages d'OF.
*   **Portail Salarié (Interface Opérateur)** : Page simplifiée optimisée pour les tablettes (`AtelierProduction`) permettant le **pointage en temps réel** ("Démarrer", "Pause", "Terminer"). La Main d'Œuvre (MOD) est ainsi tracée et valorisée instantanément.
*   **Alertes Temps Réel** : Intégration de WebPush (PWA) pour notifier les opérateurs d'urgences directement sur leur terminal, même en arrière-plan.

### 5. Tests
*   100% de réussite sur la suite de tests PestPHP (`ManufacturingOrderTest`).

## 🚧 Ce qu'il reste à faire
*   *(L'essentiel du module, incluant l'UI avancée et la logique MRP, est terminé).*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Connexion IoT et ERP** : Remontée des quantités produites et des temps de cycle directement depuis les API machines (OPC-UA).
