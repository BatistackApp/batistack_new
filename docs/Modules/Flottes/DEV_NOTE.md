# 🚐 Module Flottes (Véhicules & Matériel Lourd)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Architecture extrêmement détaillée. Modèles couvrant les Véhicules, les assignations aux conducteurs, le suivi des frais d'entretien, du carburant, et des amendes.
*   **Règles Métiers :** Contrôles de conformité croisés avec le module RH (vérification des permis de conduire), et détection d'anomalies (anti-fraude carburant).
*   **Tests :** Plus de 155 tests PestPHP validant l'ensemble de cette logique complexe (100% de réussite).
*   **Frontend :** Administration visuelle complète du parc automobile implémentée (`Filament/Flottes`). Création de la page de visualisation détaillée `ViewVehicleAssignment` avec l'Infolist du trajet et la table des États des Lieux (Check-in/Check-out). Mise à jour des composants Filament (relation managers, infolists) pour s'aligner avec l'API `Filament\Schemas` et l'utilisation des nouvelles Actions.

## 🚧 Ce qu'il reste à faire
*(L'essentiel du module et les interfaces d'administration sont terminés)*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Import Automatique des Cartes Carburant :** Écrire un connecteur API ou un importateur CSV (ex: cartes TotalEnergies, DKV) pour remonter automatiquement les dépenses de carburant et réconcilier les factures avec les chantiers.
2.  **Géolocalisation (GPS Tracking) :** Intégration avec des boîtiers télématiques GPS (ex: Webfleet, Geotab) pour localiser les véhicules en temps réel et remonter les anomalies de conduite (freinages brusques, survitesse).
3.  **App Conducteur (État des Lieux Mobile) :** Une interface PWA dédiée aux chauffeurs permettant de faire leur Check-in / Check-out directement sur le parking avec prise de photo des éventuels dommages (rayures, chocs).
4.  **Optimisation des Trajets (Routing IA) :** Utiliser une API de routing pour suggérer la répartition la plus logique des véhicules le matin en fonction des chantiers prévus, afin de minimiser le kilométrage total de la flotte.
