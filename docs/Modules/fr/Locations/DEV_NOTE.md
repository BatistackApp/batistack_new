---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🚜 Module Locations

## 📌 Vue d'ensemble du Module
Le module **Locations** permet de gérer l'ensemble des locations de matériel (pelles, grues, échafaudages) auprès de fournisseurs externes. Il assure le suivi des contrats, la refacturation ou l'imputation analytique sur les chantiers, et la facturation récurrente.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Locations` & `app/Enums/Locations`)
*   **`RentalContract` & `RentalContractLine`** : Gestion des contrats de location et détail du matériel loué (désignation, quantité, prix).
*   **Enums** : `RentalStatus` (Brouillon, Actif, Terminé) et `RentalBillingPeriod` (Journalier, Hebdomadaire, Mensuel, Annuel).

### 2. Logique Métier & Services (`app/Services/Locations`)
*   **Imputation Analytique** : `RentalCostService` calcule dynamiquement le coût analytique cumulé d'une location. Le `ChantierAnalyticService` a été modifié pour agréger en temps réel ces coûts de location dans le calcul global de la rentabilité d'un chantier.
*   **Facturation Automatisée** : `RentalBillingService` génère automatiquement une facture fournisseur brouillon (`SupplierInvoice`) basée sur la période de facturation. Intégration de la validation automatique du brouillon si le montant TTC est inférieur à 500 €.
*   **Scoring Fournisseurs (Issue #140)** : `RentalContractObserver::updateSupplierScore()` recalcule automatiquement `ThirdParty.supplier_score` (0-100) à partir de la moyenne des notes des contrats de location terminés (échelle 1-5 ×20).

### 3. Observers & Événements (`app/Observers/Locations`)
*   **Automatisation (Cron)** : `ProcessRecurringRentalsCommand` (`locations:process-billing`) génère les factures périodiques **uniquement pour les contrats arrivant à échéance** (filtre sur `next_billing_date`) — **✅ planifiée dans `routes/console.php` (02:00)** et met à jour `next_billing_date` après facturation.
*   **Alertes** : `RentalExpirationAlertJob` (échéance J-3) — **✅ dispatché quotidiennement (06:00)** avec notification **Filament DatabaseNotification + Email** au manager du chantier.
*   **`RentalContractObserver`** : Assure la cohérence des statuts et le scoring fournisseur (#140).

### 4. Interface Utilisateur (Filament)
*   **Panel Dédié** : Provider `LocationsPanelProvider`.
*   **Ressource** : `RentalContractResource` incluant Formulaire (avec Repeater), Tableau de bord (avec filtres avancés) et Infolist (vue détaillée) intégrant des actions rapides (Générer facture, Terminer location).
*   **Widgets** : `RentalCalendarWidget` (Calendrier interactif via `guava/calendar` pour visualiser la timeline des locations).
*   **Dashboard Matériel Externe (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la variance des coûts de location, le statut des contrats (Segment Bar), le budget par fournisseur (Composition) et les restitutions imminentes (Detail List).
*   **Intégration Chantiers** : Création d'un tableau de bord unifié (`DeployedResourcesWidget`) sur la vue détaillée d'un chantier, fusionnant le matériel en propre (Immobilisations) et les locations externes.

### 5. Tests
*   Des tests unitaires/fonctionnels exhaustifs couvrent l'analytique et la génération de factures. Tous passent avec succès.

### 6. Locations Sortantes (Location Client)
*   Création des modèles `OutboundRentalContract` et `OutboundRentalLine`.
*   Facturation via `OutboundRentalBillingService`.
*   Mise à jour automatique du statut des `FixedAsset` (statut `RENTED`) via `OutboundRentalObserver`.

### 7. Comparateur de Prix Fournisseurs
*   Modèle `SupplierPriceGrid` pour stocker les grilles tarifaires.
*   Page dédiée autonome `SupplierPriceComparator` (Comparateur).
*   Intégration via `HintAction` dans le `RentalContractForm`.

### 8. Dépassements et Pénalités
*   Ajout de `daily_penalty_rate`, `expected_end_date`, `penalty_amount` et `next_billing_date` sur `RentalContract`.
*   Nouveau statut **`OVERDUE`** (`RentalStatus::OVERDUE`) pour les contrats en dépassement.
*   `CheckRentalOveragesCommand` : **✅ planifiée (07:00)** — calcule les pénalités (`daily_penalty_rate` × jours de retard), met à jour `penalty_amount`, passe le statut à `OVERDUE`, envoie notifications **Filament DatabaseNotification + Email** au manager du chantier et au fournisseur. Traite les contrats `ACTIVE` et `OVERDUE` pour un cumul idempotent.
*   Alerte J-1 (contrats finissant demain) : notification `RentalExpirationAlert` basée sur `end_date`.

### 9. Facturation Interne Automatique (Refacturation)
*   **Contexte** : Une immobilisation de l'entreprise affectée à un chantier avec un tarif interne génère périodiquement une `InternalRentalInvoice` pour imputer son coût au **budget matériel** du chantier.
*   **Données** :
    *   Migration `add_internal_rental_fields_to_fixed_assets_table` : `daily_rate` (decimal nullable) et `internal_rental_period` (enum `Locations\RentalBillingPeriod`, défaut `monthly`) sur `fixed_assets`.
    *   Table `internal_rental_invoices` : `company_id`, `fixed_asset_id`, `chantier_id`, `period_start`, `period_end`, `days`, `daily_rate`, `amount_ht`, `status` (enum `Locations\InternalRentalInvoiceStatus`), `billing_key` (**unique**, anti-doublon).
*   **Modèles** : `App\Models\Locations\InternalRentalInvoice` (relations `company`, `fixedAsset`, `chantier`). Relations `internalRentalInvoices()` ajoutées sur `FixedAsset` et `Chantier`.
*   **Service** : `App\Services\Locations\InternalRentalBillingService` :
    *   `generateForAsset(FixedAsset, ?Carbon)` : calcule la période (DAILY/WEEKLY/MONTHLY/YEARLY), les jours et le montant (`days × daily_rate`), pose `billing_key = INT-{assetId}-{période}` (idempotent, retourne l'existante si non annulée).
    *   `generateDueInvoices()` : parcourt les actifs affectés (`chantier_id` non nul) avec `daily_rate` > 0.
*   **Automatisation** :
    *   `FixedAssetObserver` (Immobilisation) : dans `created()`/`updated()`, sur affectation (`chantier_id` renseigné), appelle le service Locations → génère la facture immédiate.
    *   Commande `locations:bill-internal-rentals` (`GenerateInternalRentalInvoicesCommand`) planifiée quotidiennement (03:30, fuseau Europe/Paris) dans `routes/console.php`.
*   **Analytique Chantier** : `ChantierAnalyticService::getPerformanceMetrics()` expose `internal_rental_cost_real` (somme des `amount_ht` hors `CANCELED`) intégrée au `total_cost_real`.
*   **Frontend (Filament)** :
    *   `FixedAssetForm` : section « Refacturation interne » (`daily_rate` + `internal_rental_period`).
    *   Ressource `InternalRentalInvoiceResource` (panel Locations) : listing + filtre statut/actif.
    *   Widget `ChantierFinancialOverview` : ajout du poste « + Location interne ».
*   **Tests** : `tests/Feature/Modules/Locations/InternalRentalBillingTest.php` (10 tests : idempotence, périodes, non-facturation sans chantier/tarif, intégration analytique, génération à l'affectation).

### 10. État des Lieux Mobile (Protection Litiges Fournisseurs)
*   **Contexte** : Permettre au **chef de chantier** de réaliser un état des lieux horodaté (réception/restitution) du matériel loué, pour se protéger contre les litiges fournisseurs. Fonctionne **hors-ligne** (PWA) avec synchronisation.
*   **Données** :
    *   Enum `Locations\RentalConditionReportType` : `RECEPTION` / `RESTITUTION`.
    *   Table `rental_condition_reports` : `rental_contract_id`, `type`, `comment`, `latitude`, `longitude`, `signature_checksum`, `signed_at`, `captured_at` (**posé côté serveur**), `client_key` (**unique**, anti-doublon d'idempotence).
    *   Media (Spatie) : collection `photos` (multi) et `signature` (single file).
*   **Modèle** : `App\Models\Locations\RentalConditionReport` (scopes `reception`/`restitution`/`signed`/`byContract`/`withPhotos`, méthodes `sign()`, `isSigned()`, `getPhotoCount()`, `getDisplayName()`). Relation `conditionReports()` sur `RentalContract`.
*   **Service** : `App\Services\Locations\RentalConditionReportService` :
    *   `createFromSync(User, payload)` : validation type/`client_key`, vérification que l'utilisateur **gère le chantier** du contrat (`employee.currentContract`), pose `captured_at = now()` côté serveur, et signe (checksum SHA-256) **seulement si** une signature est fournie. **Idempotent** via `client_key`.
    *   `attachPhoto(RentalConditionReport, base64)` : ajoute un média à la collection `photos`.
    *   `userManagesContract(User, RentalContract)` : le chef de chantier n'accède qu'aux contrats de ses chantiers.
*   **API** (`routes/web.php`, groupe `['auth']`) :
    *   `GET /api/etat-des-lieux/contracts` : contrats des chantiers gérés (hors statut `TERMINATED`).
    *   `POST /api/etat-des-lieux/sync` : opérations `CREATE_REPORT` + `UPLOAD_PHOTO` (par `report_key` = `client_key`), réponse `{success, processed, failed}`.
*   **Frontend (Filament)** :
    *   Page **Terrain** `EtatDesLieuxPage` (`terrain/etat-des-lieux`) + vue Blade `filament.terrain.pages.etat-des-lieux` : liste des contrats, modal réception/restitution avec commentaire, GPS, **photos caméra** (`capture="environment"`), **signature canvas**, stockage hors-ligne **IndexedDB/Dexie** (`batistack_etat_des_lieux_db`) + sync auto.
    *   `EtatDesLieuxRelationManager` (lecture seule) sur `RentalContractResource` : type, photos, commentaire, horodatage, signature, GPS.
*   **Tests** : `tests/Feature/Modules/Locations/RentalConditionReportTest.php` (7 tests : idempotence, horodatage serveur, accès limité aux chantiers gérés, type/clé invalide, signature, photo, API end-to-end).

## 🚧 Ce qu'il reste à faire
*   Tests d'intégration pour les 3 nouvelles automatisations Locations (facturation récurrente, alerte J-3, dépassements/pénalités).

## ✅ Tests ajoutés (#340)
*   **Pénalités** : `RentalCostServiceTest.php` (10 tests : getActiveDays, getPenaltyDays, getCumulativeCost, edge cases).
*   **Comparateur** : `SupplierPriceComparatorTest.php` (8 tests : modèle, calcul mensuel/semaine/jours, rejet rate manquant, tri, filtre catégorie).
*   **Locations Sortantes** : `OutboundRentalObserverTest.php` (5 tests), `OutboundRentalLineObserverTest.php` (4 tests), `OutboundRentalBillingTest.php` (9 tests), `OutboundRentalLifecycleTest.php` (3 tests).
*   **Bug corrigé** : `$fillable` de `OutboundRentalContract` manquait `last_invoice_id` ; observer `OutboundRentalLineObserver` utilisait un asset caché obsolète lors du changement de `fixed_asset_id`.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Suivi Géolocalisé** : Si du gros équipement (ex: pelles, grues) est loué avec des capteurs GPS, pouvoir remonter leur position via une API externe directement sur la fiche d'information du `RentalContract`.
