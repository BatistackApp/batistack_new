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
*   **`ManufacturingOrderObserver`** : Déclenche les actions MRP à la création et gère le cycle de vie de l'OF (ex: génération automatique d'OF lorsqu'une commande client est confirmée).

### 4. Interface Utilisateur (Filament)
*   **Espace Dédié (Panel Switch)** : Création d'un panneau Filament spécifique "Atelier & Production" (`GpaoPanelProvider`).
*   **Vues d'Administration** : 
    *   `ManufacturingOrderResource` pour la gestion classique.
    *   **Vue Kanban Sur-Mesure** (Alpine.js / Livewire) avec Drag & Drop fonctionnel pour visualiser les OFs.
    *   **Calendrier Capacitaire** (FullCalendar) pour gérer le planning interactif de la production.
    *   **Dashboard KPI Production** (Taux de qualité, coûts de production, temps de cycle).
*   **Portail Salarié (Interface Opérateur)** : Page simplifiée optimisée pour les tablettes (`AtelierProduction`) permettant le **pointage en temps réel** ("Démarrer", "Pause", "Terminer"). La Main d'Œuvre (MOD) est ainsi tracée et valorisée instantanément.
*   **Alertes Temps Réel** : Intégration de WebPush (PWA) pour notifier les opérateurs d'urgences directement sur leur terminal, même en arrière-plan.

### 5. Tests
*   100% de réussite sur la suite de tests PestPHP (`ManufacturingOrderTest`).

## 🚧 Ce qu'il reste à faire
*   *(L'essentiel du module, incluant l'UI avancée et la logique MRP, est terminé).*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Traçabilité des Lots et Numéros de Série** : Associer des numéros de lot ou de série aux matières premières consommées et aux produits finis pour une traçabilité ascendante et descendante parfaite (indispensable pour certaines normes de qualité).
