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
*   **Numérisation des Factures (OCR)** : Auto-complétion intelligente du formulaire de création (`FixedAssetForm`) via `GoogleCloudVisionOcrService` (qui détecte la date, le prix HT, le nom et déduit la catégorie). Le document (image de la facture) est sauvegardé via Spatie MediaLibrary. Optimisation des requêtes par un cache md5 du fichier.
*   **Alertes et Dashboards Avancés** : Intégration de tableaux de bord responsifs via `laboiteacode/filament-dashboard-widgets` avec indicateurs de Valeur Nette Comptable (Variance VNC globale), de la répartition des actifs (Camembert), d'un objectif de conformité VGP, et d'une liste ciblée des alertes de rentabilité et VGP.
*   **Traductions & Filtres** : Interface intégralement traduite avec filtres de recherche avancés.

### 5. Tests
*   Tests unitaires complets sur les calculs d'amortissements, d'imputation analytique et extraction OCR.

## 🚧 Ce qu'il reste à faire
*   L'essentiel du module et la connectivité avec la DGFiP (FEC) sont terminés et robustes.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Logistique de Transfert Inter-Chantiers** : Mettre en place un système de demande de mouvement pour le gros matériel (grues, pelles) générant un Bon de Transport PDF et basculant automatiquement l'imputation analytique vers le nouveau chantier.
*   **Suivi Physique et Audit d'Inventaire (PWA)** : Actuellement les QR Codes redirigent vers la fiche Filament. L'idée est de créer une vraie interface d'audit permettant de valider la présence physique de l'actif ("Vu le JJ/MM") lors des inventaires de fin d'année.
