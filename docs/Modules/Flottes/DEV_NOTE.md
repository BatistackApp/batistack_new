# 🚐 Module Flottes (Véhicules & Matériel Lourd)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Architecture extrêmement détaillée. Modèles couvrant les Véhicules, les assignations aux conducteurs, le suivi des frais d'entretien, du carburant, et des amendes.
*   **Règles Métiers :** Contrôles de conformité croisés avec le module RH (vérification des permis de conduire), et détection d'anomalies (anti-fraude carburant).
*   **Tests :** Plus de 155 tests PestPHP validant l'ensemble de cette logique complexe (100% de réussite).

## 🚧 Ce qu'il reste à faire
*   **Alertes Environnementales :** Finir de connecter la brique d'alertes météo aux fiches véhicules / conducteurs pour justifier certains sinistres.
*   **Interface Filament :** Construire l'administration visuelle complète du parc automobile.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Import Automatique des Cartes Carburant :** Écrire un connecteur API ou un importateur CSV (ex: cartes TotalEnergies, DKV) pour remonter automatiquement les dépenses de carburant, réconcilier les factures avec les chantiers et réduire la saisie manuelle.
2.  **Cartographie en Direct :** Lier les boîtiers GPS des véhicules à l'application et utiliser `filament-leaflet` pour avoir une carte affichant la position en temps réel de votre flotte et optimiser les trajets.
