# 🚐 Module Flottes (Véhicules & Matériel Lourd)

## 📌 Vue d'ensemble du Module
Le module **Flottes** gère l'ensemble du parc automobile de l'entreprise (camionnettes, engins de chantier, voitures de fonction). Il centralise les affectations aux conducteurs, le suivi de la maintenance, les coûts (carburant, péage, assurance) et la gestion des infractions.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Flottes` & `app/Enums/Flottes`)
*   **Référentiel** : `Vehicle`, `VehicleInventory` (équipements du véhicule), `VehicleContract` (leasing, assurance).
*   **Suivi Opérationnel** : `VehicleAssignment` (affectation à un conducteur), `VehicleConditionReport` (états des lieux Check-in/Check-out).
*   **Suivi des Coûts & Entretien** : `FleetExpense`, `FuelTransaction`, `TrafficFine` (amendes), `VehicleMaintenance`.
*   **Enums** : Large gamme de statuts gérés strictement (`VehicleStatus`, `AssignmentStatus`, `FineStatus`, `ConditionReportType`, etc.).

### 2. Logique Métier & Services (`app/Services/Flottes`)
*   **Gestion des Frais** : `FleetCostService`, `FleetExpenseService`, `VehicleFuelService` (qui gère désormais la réconciliation automatique des coûts de carburant avec les chantiers), et `ExpenseImportService` pour calculer le TCO (Total Cost of Ownership) et intégrer les données. Intégration partielle de prestataires (ex: `UlysApiProvider` pour le télépéage, et import de CSV TotalEnergies/DKV).
*   **Logique Métier** : `VehicleAssignmentService`, `VehicleConditionService`, `TrafficFineService` gèrent la complexité légale et opérationnelle (ex: qui conduisait au moment de l'amende).
*   **Optimisation & Sécurité** : `RoutingOptimizationService` (bases de l'optimisation des trajets) et `VehicleAlertService` (alertes sur les entretiens à venir).

### 3. Observers & Règles Métier (`app/Observers/Flottes`)
*   `VehicleAssignmentObserver`, `VehicleObserver` etc. : Contrôles de conformité croisés avec le module RH (vérification de la validité du permis de conduire avant assignation), et détection d'anomalies (anti-fraude carburant).

### 4. Interface Utilisateur (Filament)
*   **Administration visuelle complète** : Le dossier `app/Filament/Flottes` héberge le frontend du module.
*   **Ressources** : `VehicleResource` et `VehicleAssignmentResource` sont implémentées avec des vues détaillées (Infolists pour le trajet et tables des états des lieux).
*   **Refonte du Dashboard Gestionnaire de Parc** : Implémentation avancée de widgets (`laboiteacode`) pour suivre l'usure kilométrique des leasings (`max_mileage`), la variance des TCO mensuels, la composition de la flotte et les alertes de maintenance.
*   **App Conducteur (État des Lieux Mobile)** : Une interface PWA dédiée aux chauffeurs permettant de faire leur Check-in / Check-out directement sur le parking avec prise de photo des éventuels dommages (rayures, chocs).
*   **Optimisation des Trajets (Routing IA)** : Nouvelle page Filament permettant de générer automatiquement des suggestions d'affectations (Véhicules disponibles -> Chantiers actifs) en utilisant l'API Google Maps Distance Matrix, pour minimiser le kilométrage global. Validation en un clic.

### 5. Tests
*   Plus de 155 tests PestPHP validant l'ensemble de cette logique complexe (100% de réussite).

## 🚀 Ce qu'il reste à faire
- **Géolocalisation (GPS Tracking)** : Intégration avec des boîtiers télématiques GPS (ex: Webfleet, Geotab) pour localiser les véhicules en temps réel et remonter les anomalies de conduite (freinages brusques, survitesse).

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
- **Application mobile dédiée** : Amélioration de l'interface des états des lieux pour une utilisation hors-ligne.
