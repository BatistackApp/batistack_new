# 🏗️ Module Immobilisations

## 📌 Vue d'ensemble du Module
Le module **Immobilisations** permet la gestion du patrimoine de l'entreprise (machines, bâtiments, équipements de valeur). Il gère le cycle de vie de l'actif, son amortissement comptable (linéaire, dégressif), sa maintenance, son inventaire (via code-barres/QR) et son intégration analytique aux chantiers.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Immobilisation` & `app/Enums/Immobilisation`)
*   **Actifs et Catégories** : `FixedAsset`, `AssetCategory`.
*   **Cycle de Vie** : `Depreciation` (tableau d'amortissement), `AssetMaintenance` (réparations et pannes), `AssetImpairment` (perte de valeur, sinistres), `AssetDisposal` (cession ou rebut).
*   **Enums** : `AssetStatus`, `AssetType`, `DepreciationMethod` (Linéaire, Dégressif).

### 2. Logique Métier & Services (`app/Services/Immobilisation`)
*   **Comptabilité** : `DepreciationCalculatorService` pour le calcul automatisé des dotations avec gestion du prorata temporis. Recalcul dynamique en cas de dépréciation exceptionnelle (lissage de la VNC). `AssetDisposalService` pour le calcul de la plus-value/moins-value de cession.
*   **Imputation Analytique** : Consolidation automatique des coûts d'amortissement et des factures de réparation directement dans la marge brute du chantier concerné.
*   **Génération de Documents** : `ImmobilisationDocumentService` générant des plaquettes PDF avec **QR Codes** pour l'étiquetage physique et l'inventaire rapide via smartphone.
*   **Export FEC** : Service d'export générant un fichier TXT respectant les 18 colonnes de la DGFiP avec déduction des comptes (28...).

### 3. Observers & Événements (`app/Observers/Immobilisation`)
*   **`FixedAssetObserver`** : Génère automatiquement le tableau prévisionnel d'amortissement dès la création de l'actif.
*   **Commandes Artisan** : `immobilisations:run-depreciations` pour valider/clôturer l'exercice et `immobilisations:check-alerts` pour l'envoi des notifications VGP.

### 4. Interface Utilisateur (Filament)
*   **Panel Dédié** : Provider `ImmobilisationPanelProvider` accessible sur `/immobilisation`.
*   **Ressources (CRUD)** : `AssetCategoryResource` et `FixedAssetResource` (incluant les tables et formulaires V5).
*   **Alertes et Dashboards** : `AssetAlertsWidget` listant le statut des machines (VGP Expirée/Imminente, Rentabilité critique si coûts réparations > VNC) et `TotalAssetsValueWidget`.
*   **Traductions & Filtres** : Interface intégralement traduite avec filtres de recherche avancés.

### 5. Tests
*   Tests unitaires complets sur les calculs d'amortissements et d'imputation analytique (`ImputationAnalytiqueTest`).

## 🚧 Ce qu'il reste à faire
*   L'essentiel du module et la connectivité avec la DGFiP (FEC) sont terminés et robustes.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Numérisation des Factures (OCR)** : Ajouter la numérisation des factures fournisseurs via OCR lors de l'enregistrement de l'actif pour automatiser la saisie.
*   **Gestion des Subventions d'Investissement** : Automatiser le traitement comptable (étalement) des subventions d'investissement liées aux immobilisations.
