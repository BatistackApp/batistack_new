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
    *   **Widgets Analytiques** : `ChantierFinancialOverview` (marge en temps réel).
    *   **Refonte du Dashboard Directeur de Travaux** : Intégration avancée de widgets (`laboiteacode`) affichant la variance de la marge globale, le funnel des projets, la consommation d'heures et les alertes d'incidents.
    *   **Widgets Avancés** : `ChantierMapWidget` (cartographie des chantiers en cours) et `ChantierGanttWidget` (visualisation planning **avec support du Drag & Drop interactif**, décalage automatique des dépendances enfants et Livewire asynchrone).
    *   **Tableaux** : `ActiveChantiersTable`, `LatestChantiersWidget`.

### 5. Tests
*   Testé à 100% avec PestPHP (les 38 tests métier passent avec succès, y compris les tests d'évaluation financière et analytique, et de génération de DOE).

## 🚧 Ce qu'il reste à faire
*   Le socle est complet. Des optimisations d'UX (ergonomie sur mobile pour les conducteurs de travaux) peuvent être affinées.
*   **Portail Sous-Traitants (Vérification)** : Vérifier que le `SubcontractorPanel` permet bien aux sous-traitants d'interagir spécifiquement avec les tâches de chantier qui leur sont assignées et d'uploader leurs factures de situation liées à l'avancement.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **BIM (Building Information Modeling)** : Intégration en cours (voir module Vision 3D).
*   **Planning des Ressources (Resource Planner)** : Implémenter un calendrier interactif (drag & drop) pour planifier et affecter les équipes (employés) et les équipements lourds aux chantiers/tâches.
*   **Audits QSE & Checklists** : Créer un "Form Builder" pour modéliser des checklists dynamiques de contrôle de qualité ou visites de sécurité sur chantier, avec signature numérique et photos.
