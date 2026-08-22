---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 📊 Module Comptabilité

## 📌 Vue d'ensemble du Module
Le module **Comptabilité** gère le plan comptable, la génération automatique des écritures comptables issues du lettrage bancaire, et les exports comptables (FEC, Sage 50, Cegid Flow) destinés à l'expert-comptable.

## 📌 État Actuel (Ce qui est fait)

### 1. Plan Comptable (`app/Models/Accounting/CompteComptable.php`)
*   **Modèle `CompteComptable`** : numéro, libellé, classe (1-8), is_balance, parent_id.
*   **Seed PCG** : ~120 comptes du Plan Comptable Général français couvrant les 8 classes (Immobilisations, Stocks, Tiers, Financier, Charges, Produits, Résultat).
*   **Factory** + **14 tests PestPHP**.

### 2. Écritures Comptables (`app/Models/Accounting/EcritureComptable.php`)
*   **Modèle `EcritureComptable`** : date_ecriture, date_piece, journal_type (enum), numero_piece, compte_numero, libelle, debit, credit, lettrage, lettrage_status (enum), chantier_id, morphTo reconcilable.
*   **Enums** : `JournalType` (ACHATS, VENTES, BANQUE, CAISSE, OD, ANO) et `LettrageStatus` (NON_LETTRÉE, PARTIELLEMENT_LETTRÉE, LETTRÉE).
*   **Validation** : une écriture ne peut pas avoir à la fois débit et crédit non nuls.
*   **Scopes** : `deDateRange`, `duJournal`, `nonLettrées`, `lettrées`, `duChantier`.
*   **Factory** + **16 tests PestPHP**.

### 3. Services (`app/Services/Accounting/`)
*   **`EcritureComptableService`** : `createBalancedPair`, `lettrer`, `dellettrer`, `getSoldeCompte`, `getBalanceCompte`, `generateNumeroPiece` (séquentiel JOURNAL-YYYYMMDD-NNNN).
*   **`AccountingPlanService`** : mapping catégories de transactions bancaires → comptes comptables (Salaires→6411/5121, Fournisseurs→4011/5121, Clients→5121/4111, etc.).
*   **10 tests PestPHP** sur `AccountingPlanService`.

### 4. Observer — Génération automatique (`app/Observers/Banque/BankReconciliationObserver.php`)
*   **`created`** : génère une paire d'écritures équilibrées (débit/crédit) au moment du lettrage bancaire. Le compte débit dépend du type de transaction (DEBIT→compte charge par défaut, CREDIT→compte client).
*   **`deleted`** : supprime les écritures comptables liées au lettrage annulé.
*   **`updated`** : pas de régénération (conservé pour éviter les doublons).

### 5. Exports Comptables (`app/Services/Accounting/`)

#### FEC — Fichier des Écritures Comptables (`FecExportService`)
*   **18 colonnes** obligatoires (standard FEC français).
*   **`exportFec($year, $siren)`** : génère le fichier `SIRENFECYYYYMMDD.txt`.
*   **`getFecData($year)`** : retourne les données formatées pour preview UI.
*   **`getBalanceGenerale($year)`** : balance par compte avec totaux débit/crédit et soldes.
*   **8 tests PestPHP**.

#### Sage 50 (`Sage50ExportService`)
*   **CSV 12 colonnes** (séparateur `;`) : JournalCode, JournalLib, EcritureNum, EcritureDate, CompteNum, CompteLib, PieceRef, PieceDate, EcritureLib, Debit, Credit, EcritureLet.
*   **`exportCsv($year)`** : génère le fichier `export_sage50_YYYY_YYYYMMDD_HHMMSS.csv`.
*   **`getData($year)`** : preview UI.
*   **6 tests PestPHP**.

#### Cegid Flow (`CegidFlowExportService`)
*   **CSV 11 colonnes** (séparateur `;`) : JournalCode, EcritureDate, CompteNum, CompteAuxNum, EcritureLib, Debit, Credit, PieceRef, EcritureLet, Devise, Lettrage.
*   **`exportCsv($year)`** : génère le fichier `export_cegid_flow_YYYY_YYYYMMDD_HHMMSS.csv`.
*   **`getData($year)`** : preview UI.
*   **6 tests PestPHP**.

### 6. Interface Utilisateur (Filament)
*   **`AccountingExportPage`** : page dédiée dans le panel Banque (menu « Comptabilité » → « Export Comptable »).
    *   Sélecteur d'année + sélecteur de format (FEC / Sage 50 / Cegid Flow).
    *   Bouton « Générer l'export » → téléchargement du fichier.
    *   Bouton « Aperçu » → tableau des écritures avant export.
*   **`CompteComptableResource`** : CRUD Filament pour le plan comptable (à venir en itération suivante).

### 7. Tests — Couverture Complète
*   **60 tests PestPHP**, **145 assertions** — tous verts.
*   Répartis dans `tests/Feature/Modules/Accounting/` :
    *   `CompteComptableTest.php` (14 tests)
    *   `EcritureComptableTest.php` (16 tests)
    *   `AccountingPlanServiceTest.php` (10 tests)
    *   `FecExportServiceTest.php` (8 tests)
    *   `Sage50ExportServiceTest.php` (6 tests)
    *   `CegidFlowExportServiceTest.php` (6 tests)

## 🔧 Fichiers Clés

| Fichier | Rôle |
|---------|------|
| `app/Models/Accounting/CompteComptable.php` | Plan comptable |
| `app/Models/Accounting/EcritureComptable.php` | Écritures comptables |
| `app/Enums/Accounting/JournalType.php` | Types de journaux |
| `app/Enums/Accounting/LetrageStatus.php` | Statuts de lettrage |
| `app/Services/Accounting/EcritureComptableService.php` | Service d'écritures |
| `app/Services/Accounting/AccountingPlanService.php` | Mapping catégories→comptes |
| `app/Services/Accounting/FecExportService.php` | Export FEC |
| `app/Services/Accounting/Sage50ExportService.php` | Export Sage 50 |
| `app/Services/Accounting/CegidFlowExportService.php` | Export Cegid Flow |
| `app/Observers/Banque/BankReconciliationObserver.php` | Hook de génération |
| `app/Filament/Banque/Resources/Accounting/AccountingExportPage.php` | Page UI export |
| `database/seeders/Accounting/PcgSeeder.php` | Seed PCG ~120 comptes |
| `database/migrations/2026_08_23_001000_create_compte_comptables_table.php` | Migration plan comptable |
| `database/migrations/2026_08_23_001001_create_ecriture_comptables_table.php` | Migration écritures |
| `resources/views/filament/pages/accounting-export.blade.php` | Vue Blade export |

## 📂 Tests

```bash
php artisan test tests/Feature/Modules/Accounting/
```

## 💡 Comment tester par l'UI

1. **Aller** dans le panel **Banque** → menu **Comptabilité** → **Export Comptable**.
2. **Sélectionner l'année** (ex: 2026) et le **format** (FEC, Sage 50, ou Cegid Flow).
3. Cliquer sur **Aperçu** pour voir les écritures comptables dans un tableau.
4. Cliquer sur **Générer l'export** pour télécharger le fichier CSV/TXT.

> **Note** : les écritures comptables sont générées automatiquement lors du lettrage bancaire. Pour en créer manuellement, utiliser le `EcritureComptableService` en tâche de fond ou via Artisan.

## 🚧 Ce qu'il reste à faire
*   **CRUD Filament** pour le plan comptable (`CompteComptableResource`).
*   **Génération automatique** des écritures depuis les factures (hors banque) — actuellement uniquement via le lettrage bancaire.
*   **Page de balance** dans Filament (visualisation de la balance générale par exercice).
*   **Export FEC des amortissements** : réintégrer les écritures d'amortissement dans le FEC complet (actuellement enregistrées séparément dans l'ancien `FecExportService`).
*   **Mapping configurable** : admin panel pour mapper les catégories de transactions → comptes comptables (actuellement codé en dur dans `AccountingPlanService`).
