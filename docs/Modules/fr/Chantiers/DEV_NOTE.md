---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🏗️ Module Chantiers (Gestion de Projets BTP)

## 📌 Vue d'ensemble du Module
Le module **Chantiers** est le cœur de la gestion de projets BTP de l'ERP. Il permet d'orchestrer les phases de construction, la budgétisation, le suivi analytique, le journal de bord quotidien, la documentation technique (DOE) et la cartographie des chantiers.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Chantiers` & `app/Enums/Chantiers`)
*   **`Chantier` & `ChantierPhase` & `ChantierTask`** : Structuration complète d'un projet de sa création (brouillon) à sa livraison, découpé en phases et en tâches.
*   **`ChantierLog`** : Journal de bord (météo, personnel présent, incidents). **Support de la dictée vocale (Speech-to-Text)** natif et optimisé PWA ajouté.
*   **`DoeDocument`** : Suivi des Dossiers d'Ouvrages Exécutés (DOE).
*   **`WeatherAlert`** : Intégration pour la sécurité météo sur chantiers.

### 2. Logique Métier & Services (`app/Services/Chantiers`)
*   **`ChantierAnalyticService`** : La gestion des imputations de coûts et du suivi financier par chantier est en place, incluant l'intégration des **coûts de flotte**, des **coûts matériaux réels**, et des **coûts d'immobilisation de l'outillage/gros matériel (IoT)**.
*   **`ChantierDocumentService` & `DoeDocumentService`** : Les fiches techniques des articles sont automatiquement incluses dans la génération du DOE. Compilation des médias et fiches validée.
*   **`ChantierWorkflowService`** : Orchestration et automatisation des changements de statuts.
*   **`ChantierLogService`** : Logique du journal de chantier quotidien.

### 3. Observers & Événements (`app/Observers/Chantiers`)
*   **`ChantierObserver`, `ChantierTaskObserver`, `ChantierLogObserver`** : Garantissent l'intégrité des données financières (recalcul des marges) et la génération automatique des références (ex: CH-YYYY-001).

### 4. Interface Utilisateur (Filament)
*   **Panel Chantiers (`app/Filament/Chantier`)** : Contrairement aux spécifications initiales, **l'interface utilisateur est entièrement développée et robuste**. Le panel dédié comprend :
    *   **Ressources** : `ChantierResource` (Formulaires et Infolists complexes, Vue détaillée) et `ChantierLogResource` (Journal de bord).
    *   **Planning des Ressources** : Page interactive personnalisée (`ResourcePlanner`) avec Drag & Drop Alpine.js pour l'affectation sans conflit d'employés et de véhicules sur les tâches.
    *   **Widgets Analytiques** : `ChantierFinancialOverview` (marge en temps réel).
    *   **Refonte du Dashboard Directeur de Travaux** : Intégration avancée de widgets (`laboiteacode`) affichant la variance de la marge globale, le funnel des projets, la consommation d'heures et les alertes d'incidents.
    *   **Widgets Avancés** : `ChantierMapWidget` (cartographie des chantiers en cours) et `ChantierGanttWidget` (visualisation planning **avec support du Drag & Drop interactif**, décalage automatique des dépendances enfants et Livewire asynchrone).
    *   **Tableaux** : `ActiveChantiersTable`, `LatestChantiersWidget`.

### 5. Tests
*   Testé à 100% avec PestPHP (les 39 tests métier passent avec succès, y compris les tests d'évaluation financière, analytique, de génération de DOE, et de prévention de double-réservation `ResourcePlannerTest`).
*   Couverture complète des Checklists Dynamiques (`ChecklistTest.php`) avec vérification du rendu, soumission et génération de signature.

### 6. Audits QSE & Checklists Dynamiques
*   **Création de modèles dynamiques** : `ChecklistTemplate` permet via le champ Builder de créer des formulaires JSON (texte, cases à cocher, photos).
*   **Remplissage et soumission** : Page dédiée `FillChecklistPage` convertissant les modèles JSON en formulaires natifs Filament.
*   **Signature électronique** : Intégration de `saade/filament-autograph` pour la validation sur le terrain.

### 7. Génération PPSPS & Levée des Réserves (Issues #282, #281)
*   **PPSPS (Sécurité)** : `PpspsService` compile le Plan Particulier de Sécurité et de Protection de la Santé (tâches, matériel alloué, fiches de sécurité produits) et génère le PDF via `ChantierDocumentService::generatePpsps()` (vue `pdf/chantiers/ppsps.blade.php`, 7 sections). Actions UI sur la liste et la vue du chantier + job asynchrone.
*   **Levée des Réserves / OPR (Snagging)** : `ChantierReserve` (statut, sévérité, assignation, échéance) géré via `ReservesRelationManager` (titre « Réserves / OPR ») avec workflow complet : création, assignation, résolution, **levée par le client avec signature électronique** (`SignaturePad`), photos/plan joints.

## 🚧 Ce qu'il reste à faire
*   Le socle est complet. Les optimisations d'UX (ergonomie mobile pour les conducteurs de travaux) peuvent être affinées.
*   **Pointage Matériel IoT (Issue #127)** : Le coût d'immobilisation du matériel est imputé au chantier, mais le **tracking physique** d'entrée/sortie du gros matériel (capteurs IoT ou QR Codes) n'est pas implémenté.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Ergonomie mobile des chantiers** : Affiner l'expérience des conducteurs de travaux sur mobile (formulaires, navigation, hors-ligne PWA).
