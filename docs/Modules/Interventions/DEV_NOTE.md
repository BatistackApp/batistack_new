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

*   **Frontend (Panels Filament) :**
    *   **Espace Administrateur** : Ressource `Intervention` pleinement opérationnelle avec création, édition, modification, déstockage et génération de factures (via PanelSwitch).
    *   **Espace Technicien SAV** : Panel dédié et sécurisé (`/technicien`) protégé par le middleware `EnsureUserIsTechnician`. L'interface de l'intervention est simplifiée, restreinte aux interventions assignées et passées à l'état "Planifiée" ou supérieur. Les données financières sont masquées en lecture seule.
*   **Signature & PDF :** 
    *   Intégration du composant de signature électronique (`filament-autograph`) directement en action sur la table.
    *   Génération automatique d'un Token (UUID) pour le scellement cryptographique des signatures en présentiel.
    *   Génération du "Bon de Travail" PDF (sans prix) avec apposition automatique du certificat de signature via Puppeteer/Browsershot (corrigé pour supporter le typage strict des Enums).
*   **Stabilité & Corrections (Live) :**
    *   Correction de l'assignation automatique de la `company_id` par défaut lors de la création (`InterventionObserver`).
    *   Correction du rendu des Enums dans les templates Blade PDF.
    *   Gestion de l'affichage du Token dans le cas des signatures sans demande préalable.

## 🚧 Ce qu'il reste à faire
*(Le socle initial du module est terminé)*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Application Mobile Technicien (PWA/Offline)** : Créer une interface simplifiée (Offline-first) pour le technicien sur le terrain, permettant de synchroniser les interventions réalisées et les pièces utilisées une fois la connexion réseau retrouvée.
2.  **Tracking GPS des Camions** : Lier les interventions à la géolocalisation des flottes pour optimiser automatiquement les tournées des techniciens (calcul de l'itinéraire le plus court via l'API Google Maps ou OSRM).
3.  **QR Code Matériel** : Scanner directement les pièces de rechange depuis le camion avec l'appareil photo du smartphone pour les ajouter automatiquement à la liste des matériaux consommés.
4.  **Historique Prédictif** : Analyser la fréquence des pannes sur certains équipements clients pour proposer des contrats de maintenance préventifs de manière proactive (Machine Learning basique).
