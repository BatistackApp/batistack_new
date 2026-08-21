---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

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
*   **Subventions d'Investissement (Issue #138)** : Champs `grant_amount` / `grant_name` sur `FixedAsset` avec suivi de la reprise de subvention au même rythme que l'amortissement (Norme PCG) intégré au tableau d'amortissement (`DepreciationCalculatorService::applyGrantToSchedule`).
*   **Imputation Analytique** : Consolidation automatique des coûts d'amortissement et des factures de réparation directement dans la marge brute du chantier concerné.
*   **Génération de Documents** : `ImmobilisationDocumentService` générant des plaquettes PDF avec **QR Codes** pour l'étiquetage physique et l'inventaire rapide via smartphone.
*   **Export FEC** : Service d'export générant un fichier TXT respectant les 18 colonnes de la DGFiP avec déduction des comptes (28...).

### 3. Observers & Événements (`app/Observers/Immobilisation`)
*   **`FixedAssetObserver`** : Génère automatiquement le tableau prévisionnel d'amortissement dès la création de l'actif.
*   **Commandes Artisan** : `immobilisations:run-depreciations` pour valider/clôturer l'exercice et `immobilisations:check-alerts` pour l'envoi des notifications VGP.

### 4. Interface Utilisateur (Filament)
*   **Panel Dédié** : Provider `ImmobilisationPanelProvider` accessible sur `/immobilisation`.
*   **Ressources (CRUD)** : `AssetCategoryResource` et `FixedAssetResource` (incluant les tables et formulaires V5).
*   **Numérisation des Factures (OCR)** : Auto-complétion intelligente du formulaire de création (`FixedAssetForm`) via `GoogleCloudVisionOcrService` (qui détecte la date, le prix HT, le nom et déduit la catégorie). L'interface `OcrServiceInterface` est liée au service concret dans le conteneur (`AppServiceProvider`). Le document (image de la facture) est sauvegardé via Spatie MediaLibrary. Optimisation des requêtes par un cache du fichier (empreinte **SHA-256**). Si l'OCR est désactivé ou la clé API manquante, l'extraction retourne une valeur **vide** (aucune donnée inventée).
*   **Alertes et Dashboards Avancés** : Intégration de tableaux de bord responsifs via `laboiteacode/filament-dashboard-widgets` avec indicateurs de Valeur Nette Comptable (Variance VNC globale), de la répartition des actifs (Camembert), d'un objectif de conformité VGP, et d'une liste ciblée des alertes de rentabilité et VGP.
*   **Traductions & Filtres** : Interface intégralement traduite avec filtres de recherche avancés.

### 5. Tests
*   Tests unitaires complets sur les calculs d'amortissements, d'imputation analytique et extraction OCR.

### 6. Transfert et Inventaire
*   **Transfert Inter-Chantiers** : Demandes de mouvements avec `AssetTransfer`, génération de Bon de Transport (PDF) et mise à jour de l'imputation analytique de l'actif.
*   **Audit d'Inventaire** : Interface dédiée de scan PWA (`InventoryAudit`) pour valider la présence physique d'un actif via son QR Code.

### 7. Portail de Déclaration de Casse / Sinistre (PWA Salarié)
*   **Ticket de casse** : `AssetMaintenanceTicket` (morph `asset` → `FixedAsset` **ou** `Equipement` RH) avec statut (Ouvert / En cours / Résolu / Annulé), gravité, photos (Spatie MediaLibrary) et référence atomique `TK-AAAA-NNNN` (`AssetMaintenanceTicketObserver`).
*   **QR Codes** : champ `qr_token` unique généré automatiquement sur `FixedAsset` et `Equipement` (observers) + étiquette PDF imprimable (`ImmobilisationDocumentService::generateQrLabel`).
*   **Service** : `AssetMaintenanceTicketService` (résolution d'actif par code, création de ticket avec passage en maintenance, prise en charge, résolution → création d'un `AssetMaintenance` curatif pour les FixedAssets, annulation, notification interne du dépôt).
*   **Page Salarié** : `DeclarationCassePage` (scan caméra `BarcodeInput` → détection → formulaire → ticket + notification database aux admins).
*   **Triage Dépôt** : Resource `AssetMaintenanceTickets` dans le panel Immobilisations (filtres statut/gravité/type, actions workflow).
*   **Tests** : `AssetMaintenanceTicketTest` (15 tests) + extensions `ImmobilisationDocumentServiceTest`.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Subventions (Issue #138)** : Option de déduction de la subvention de la **base amortissable** (`baseValue`) en plus de la reprise proportionnelle actuellement implémentée (méthode alternative à la norme PCG).
