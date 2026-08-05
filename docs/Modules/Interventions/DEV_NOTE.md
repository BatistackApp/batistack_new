# 🔧 Module Interventions (SAV et Dépannages)

## 📌 Vue d'ensemble du Module
Le module **Interventions** permet la gestion complète du service après-vente (SAV), des dépannages et de la maintenance chez les clients. Il offre un espace dédié pour les techniciens sur le terrain et se synchronise parfaitement avec les stocks et la facturation.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Interventions` & `app/Enums/Interventions`)
*   **Modèles** : `Intervention`, `InterventionWorker` (techniciens assignés), `InterventionMaterial` (pièces détachées et consommables utilisés), `ClientEquipment` (matériel client pour le suivi prédictif).
*   **Enums** : `InterventionStatus` (Planifiée, En cours, Terminée, etc.) et `InterventionType` (Régie, Forfait).

### 2. Logique Métier & Services (`app/Services/Interventions`)
*   **Costing & Rentabilité (`InterventionCostingService`)** : Calcul précis de la rentabilité prenant en charge les interventions "Forfaitaires" (prix fixe) et en "Régie" (facturation selon le matériel utilisé et le temps passé).
*   **Gestion des Stocks (`InterventionStockService`)** : Déstockage automatique des pièces détachées (`StockMouvementService`) relié à l'entrepôt ou au camion du technicien lorsque l'intervention bascule au statut "Terminée".
*   **Facturation (`InterventionBillingService`)** : Génération automatique d'une facture client brouillon (`CustomerInvoice`) détaillée (TVA standard, responsable assigné) à la clôture de l'intervention.
*   **Documents & Signatures (`InterventionPdfService`)** : Génération d'un "Bon de Travail" PDF (sans prix) avec apposition automatique du certificat de signature cryptographique via Puppeteer/Browsershot.
*   **Maintenance Prédictive (`PredictiveMaintenanceService`)** : Analyse de la fréquence des pannes (MTBF) sur le matériel client (`ClientEquipment`) et proposition proactive de contrats de maintenance sous forme de devis brouillon (`CustomerQuote`).

### 3. Observers & Événements (`app/Observers/Interventions`)
*   **`InterventionObserver`** : Génération automatique des références (ex: INT-YYYY-001) et correction de l'assignation automatique de la `company_id`.
*   **Notifications** : Alertes programmées (`InterventionScheduledNotification`) via Database et WebPush pour avertir les techniciens et les clients.

### 4. Interface Utilisateur (Filament)
*   **Espace Administrateur** : Panel complet pour la gestion, l'édition, le suivi du déstockage et la génération de factures (via PanelSwitch).
*   **Espace Technicien SAV** : Panel dédié et sécurisé (`/technicien`) protégé par le middleware `EnsureUserIsTechnician`. L'interface est simplifiée, restreinte aux interventions assignées ("Planifiée" ou supérieur). Les données financières y sont masquées en lecture seule.
*   **Signature Client** : Intégration du composant de signature électronique (`filament-autograph`) directement en action sur la table. Toute modification ultérieure invalide automatiquement la signature (génération d'un Token UUID pour scellement cryptographique).
*   **QR Code Matériel** : Intégration d'un scanner permettant d'ajouter rapidement des pièces détachées directement depuis le camion via un smartphone.
*   **Dashboard SAV (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la rentabilité du SAV (Variance), le respect des SLA (Goal), l'entonnoir des interventions (Funnel) et les alertes urgentes (Detail List).

### 5. Tests
*   Couverture robuste avec PestPHP. L'intégralité de la logique métier (gestion, facturation, maintenance prédictive, optimisation d'itinéraire), du déstockage automatique, de la facturation, des signatures, et des contraintes d'intégrité passe avec succès (100% de réussite). Les composants mineurs et les observers sont également couverts.

## 🚧 Ce qu'il reste à faire
*   Le socle initial du module est terminé et opérationnel, y compris avec les signatures cryptographiques.
*   **Portail Client (Vérification)** : Un portail client est déjà préparé. Il faut s'assurer qu'il permet bien au client de visualiser son parc de matériel (`ClientEquipment`) et de déclarer une panne/demander une intervention sur cet équipement spécifique.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités

