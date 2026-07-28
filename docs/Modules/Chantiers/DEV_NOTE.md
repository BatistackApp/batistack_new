# 🏗️ Module Chantiers (Gestion de Projets BTP)

## 📌 Vue d'ensemble du Module
Le module **Chantiers** est le cœur de la gestion de projets BTP de l'ERP. Il permet d'orchestrer les phases de construction, la budgétisation, le suivi analytique, le journal de bord quotidien, la documentation technique (DOE) et la cartographie des chantiers.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Chantiers` & `app/Enums/Chantiers`)
*   **`Chantier` & `ChantierPhase` & `ChantierTask`** : Structuration complète d'un projet de sa création (brouillon) à sa livraison, découpé en phases et en tâches.
*   **`ChantierLog`** : Journal de bord (météo, personnel présent, incidents).
*   **`DoeDocument`** : Suivi des Dossiers d'Ouvrages Exécutés (DOE).
*   **`WeatherAlert`** : Intégration pour la sécurité météo sur chantiers.

### 2. Logique Métier & Services (`app/Services/Chantiers`)
*   **`ChantierAnalyticService`** : La gestion des imputations de coûts et du suivi financier par chantier est en place, incluant l'intégration des **coûts de flotte** et des **coûts matériaux réels**.
*   **`ChantierDocumentService` & `DoeDocumentService`** : Les fiches techniques des articles sont automatiquement incluses dans la génération du DOE. Compilation des médias et fiches validée.
*   **`ChantierWorkflowService`** : Orchestration et automatisation des changements de statuts.
*   **`ChantierLogService`** : Logique du journal de chantier quotidien.

### 3. Observers & Événements (`app/Observers/Chantiers`)
*   **`ChantierObserver`, `ChantierTaskObserver`, `ChantierLogObserver`** : Garantissent l'intégrité des données financières (recalcul des marges) et la génération automatique des références (ex: CH-YYYY-001).

### 4. Interface Utilisateur (Filament)
*   **Panel Chantiers (`app/Filament/Chantier`)** : Contrairement aux spécifications initiales, **l'interface utilisateur est entièrement développée et robuste**. Le panel dédié comprend :
    *   **Ressources** : `ChantierResource` (Formulaires et Infolists complexes, Vue détaillée) et `ChantierLogResource` (Journal de bord).
    *   **Widgets Analytiques** : `ChantierStatsOverview`, `ChantierFinancialOverview` (marge en temps réel).
    *   **Widgets Avancés** : `ChantierMapWidget` (cartographie des chantiers en cours) et `ChantierGanttWidget` (visualisation planning).
    *   **Tableaux** : `ActiveChantiersTable`, `LatestChantiersWidget`.

### 5. Tests
*   Testé à 100% avec PestPHP (les 38 tests métier passent avec succès, y compris les tests d'évaluation financière et analytique, et de génération de DOE).

## 🚧 Ce qu'il reste à faire
*   Le socle est complet. Des optimisations d'UX (ergonomie sur mobile pour les conducteurs de travaux) peuvent être affinées.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Planification Gantt (Interactive)** : Améliorer le widget Gantt actuel pour le rendre interactif, permettant de déplacer visuellement (Drag & Drop) les phases du chantier et l'affectation des équipes, avec recalcul automatique des dépendances.
*   **Suivi de Chantier Mobile (Speech-to-Text)** : Via une PWA, permettre aux conducteurs de travaux d'utiliser la reconnaissance vocale pour dicter leur rapport de visite et le retranscrire automatiquement dans le journal de bord.
*   **Module de Pointage Matériel (IoT)** : Intégrer des capteurs IoT ou des QR Codes pour tracker l'entrée/sortie du gros matériel sur le chantier et imputer le coût d'immobilisation de manière automatisée.
*   **BIM (Building Information Modeling)** : Intégrer une visionneuse 3D de maquettes BIM (ex: Forge viewer) pour lier visuellement les tâches aux éléments de la maquette (cliquer sur un mur pour voir les tâches associées).
