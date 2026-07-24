# 🔧 Module Interventions (SAV et Dépannages)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Architecture complète incluant le modèle `Intervention`, le suivi des techniciens (`InterventionWorker`), et le suivi du matériel utilisé (`InterventionMaterial`). Génération automatique des références via `InterventionObserver` (ex: INT-YYYY-001).
*   **Gestion Comptable (Services) :**
    *   **Costing :** Calcul de rentabilité précis prenant en charge les interventions "Forfaitaires" (prix fixe) et les interventions en "Régie" (facturation selon le matériel utilisé et le temps passé).
    *   **Stock :** Déstockage automatique des pièces détachées (`StockMouvementService`) relié à l'entrepôt ou au camion du technicien lorsque l'intervention bascule au statut "Terminée".
    *   **Facturation :** Génération automatique d'une facture client brouillon (`CustomerInvoice`) détaillée (TVA standard, responsable assigné) à la clôture de l'intervention.
*   **Notifications :** Alertes programmées via `InterventionScheduledNotification` (Database + WebPush) pour avertir les techniciens et les clients.
*   **Tests :** Couverture robuste avec PestPHP. L'intégralité de la logique métier, du déstockage automatique, de la facturation et des contraintes d'intégrité de la base de données passe avec succès (100% de réussite).

## 🚧 Ce qu'il reste à faire
*   **Frontend (Panel Filament) :** 
    *   Création de la `Resource` Intervention.
    *   Implémentation des formulaires de saisie (choix du client, du type, sélection des pièces de rechange et du camion/entrepôt source).
    *   Mise en place des `Actions` Filament pour générer le rapport PDF d'intervention et envoyer l'e-mail avec signature électronique.
*   **Signature Client :** Intégration du composant de signature électronique en fin d'intervention pour validation sur tablette.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Application Mobile Technicien (PWA/Offline) :** Créer une interface simplifiée (Offline-first) pour le technicien sur le terrain, permettant de synchroniser les interventions réalisées et les pièces utilisées une fois la connexion réseau retrouvée.
2.  **Tracking GPS des Camions :** Lier les interventions à la géolocalisation des flottes pour optimiser automatiquement les tournées des techniciens (calcul de l'itinéraire le plus court via l'API Google Maps ou OSRM).
3.  **QR Code Matériel :** Scanner directement les pièces de rechange depuis le camion avec l'appareil photo du smartphone pour les ajouter automatiquement à la liste des matériaux consommés.
4.  **Historique Prédictif :** Analyser la fréquence des pannes sur certains équipements clients pour proposer des contrats de maintenance préventifs de manière proactive (Machine Learning basique).
