# 🔧 Module Interventions (SAV et Dépannages)

## 📌 Vue d'ensemble du Module
Le module **Interventions** permet la gestion complète du service après-vente (SAV), des dépannages et de la maintenance chez les clients. Il offre un espace dédié pour les techniciens sur le terrain et se synchronise parfaitement avec les stocks et la facturation.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Interventions` & `app/Enums/Interventions`)
*   **Modèles** : `Intervention`, `InterventionWorker` (techniciens assignés), `InterventionMaterial` (pièces détachées et consommables utilisés).
*   **Enums** : `InterventionStatus` (Planifiée, En cours, Terminée, etc.) et `InterventionType` (Régie, Forfait).

### 2. Logique Métier & Services (`app/Services/Interventions`)
*   **Costing & Rentabilité (`InterventionCostingService`)** : Calcul précis de la rentabilité prenant en charge les interventions "Forfaitaires" (prix fixe) et en "Régie" (facturation selon le matériel utilisé et le temps passé).
*   **Gestion des Stocks (`InterventionStockService`)** : Déstockage automatique des pièces détachées (`StockMouvementService`) relié à l'entrepôt ou au camion du technicien lorsque l'intervention bascule au statut "Terminée".
*   **Facturation (`InterventionBillingService`)** : Génération automatique d'une facture client brouillon (`CustomerInvoice`) détaillée (TVA standard, responsable assigné) à la clôture de l'intervention.
*   **Documents & Signatures (`InterventionPdfService`)** : Génération d'un "Bon de Travail" PDF (sans prix) avec apposition automatique du certificat de signature cryptographique via Puppeteer/Browsershot.

### 3. Observers & Événements (`app/Observers/Interventions`)
*   **`InterventionObserver`** : Génération automatique des références (ex: INT-YYYY-001) et correction de l'assignation automatique de la `company_id`.
*   **Notifications** : Alertes programmées (`InterventionScheduledNotification`) via Database et WebPush pour avertir les techniciens et les clients.

### 4. Interface Utilisateur (Filament)
*   **Espace Administrateur** : Panel complet pour la gestion, l'édition, le suivi du déstockage et la génération de factures (via PanelSwitch).
*   **Espace Technicien SAV** : Panel dédié et sécurisé (`/technicien`) protégé par le middleware `EnsureUserIsTechnician`. L'interface est simplifiée, restreinte aux interventions assignées ("Planifiée" ou supérieur). Les données financières y sont masquées en lecture seule.
*   **Signature Client** : Intégration du composant de signature électronique (`filament-autograph`) directement en action sur la table. Toute modification ultérieure invalide automatiquement la signature (génération d'un Token UUID pour scellement cryptographique).

### 5. Tests
*   Couverture robuste avec PestPHP. L'intégralité de la logique métier, du déstockage automatique, de la facturation, des signatures, et des contraintes d'intégrité de la base de données passe avec succès (100% de réussite).

## 🚧 Ce qu'il reste à faire
*   Le socle initial du module est terminé et opérationnel, y compris avec les signatures cryptographiques.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Application Mobile Technicien (PWA/Offline)** : Créer une interface simplifiée (Offline-first) pour le technicien sur le terrain, permettant de synchroniser les interventions réalisées et les pièces utilisées une fois la connexion réseau retrouvée.
*   **Tracking GPS des Camions** : Lier les interventions à la géolocalisation des flottes pour optimiser automatiquement les tournées des techniciens (calcul de l'itinéraire le plus court via l'API Google Maps ou OSRM).
*   **QR Code Matériel** : Scanner directement les pièces de rechange depuis le camion avec l'appareil photo du smartphone pour les ajouter automatiquement à la liste des matériaux consommés.
*   **Historique Prédictif** : Analyser la fréquence des pannes sur certains équipements clients pour proposer des contrats de maintenance préventifs de manière proactive (Machine Learning basique).
