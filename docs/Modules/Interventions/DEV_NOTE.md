# 🔧 Module Interventions (SAV et Dépannages)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Architecture complète incluant le modèle `Intervention`, le suivi des techniciens (`InterventionWorker`), et le suivi du matériel utilisé (`InterventionMaterial`). Génération automatique des références via `InterventionObserver` (ex: INT-YYYY-001).
*   **Gestion Comptable (Services) :**
    *   **Costing :** Calcul de rentabilité précis prenant en charge les interventions "Forfaitaires" (prix fixe) et les interventions en "Régie" (facturation selon le matériel utilisé et le temps passé).
    *   **Stock :** Déstockage automatique des pièces détachées (`StockMouvementService`) relié à l'entrepôt ou au camion du technicien lorsque l'intervention bascule au statut "Terminée".
    *   **Facturation :** Génération automatique d'une facture client brouillon (`CustomerInvoice`) détaillée (TVA standard, responsable assigné) à la clôture de l'intervention.
*   **Notifications :** Alertes programmées via `InterventionScheduledNotification` (Database + WebPush) pour avertir les techniciens et les clients.
*   **Signature Client :** Intégration du `SignatureService` avec relation polymorphique sur le modèle. Une signature scelle l'intervention (génération d'un checksum) : toute modification ultérieure invalide automatiquement la signature (approche souple validée).
*   **Tests :** Couverture robuste avec PestPHP. L'intégralité de la logique métier, du déstockage automatique, de la facturation, des signatures, et des contraintes d'intégrité de la base de données passe avec succès (100% de réussite).

*   **Frontend (Panel Filament) :**
    *   Ressource `Intervention` pleinement opérationnelle dans le panel dédié (via PanelSwitch).
    *   Formulaire adaptatif divisé en 3 sections (Général, Équipe, Matériel) supportant le déstockage et le costing.
    *   Table de suivi avec statuts dynamiques et filtres.
*   **Signature & PDF :** Intégration du composant de signature électronique (`filament-autograph`) directement en action sur la table. Génération du "Bon de Travail" PDF (sans prix) avec apposition automatique du certificat de signature via Puppeteer/Browsershot. Actions de conversion de l'intervention en facture dans le module Commerce intégrées et fonctionnelles.

## 🚧 Ce qu'il reste à faire
*(Le socle initial du module est terminé)*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Application Mobile Technicien (PWA/Offline)** : Créer une interface simplifiée (Offline-first) pour le technicien sur le terrain, permettant de synchroniser les interventions réalisées et les pièces utilisées une fois la connexion réseau retrouvée.
2.  **Tracking GPS des Camions** : Lier les interventions à la géolocalisation des flottes pour optimiser automatiquement les tournées des techniciens (calcul de l'itinéraire le plus court via l'API Google Maps ou OSRM).
3.  **QR Code Matériel** : Scanner directement les pièces de rechange depuis le camion avec l'appareil photo du smartphone pour les ajouter automatiquement à la liste des matériaux consommés.
4.  **Historique Prédictif** : Analyser la fréquence des pannes sur certains équipements clients pour proposer des contrats de maintenance préventifs de manière proactive (Machine Learning basique).
