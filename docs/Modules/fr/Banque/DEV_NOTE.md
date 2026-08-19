---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🏦 Module Banque & Rapprochement Bancaire

## 📌 Vue d'ensemble du Module
Le module **Banque** gère la trésorerie de l'entreprise. Il permet de connecter les comptes bancaires via Open Banking, de synchroniser les transactions, et de lettrer automatiquement ou manuellement les encaissements/décaissements avec les factures clients, factures fournisseurs, et fiches de paie.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Banque` & `app/Enums/Banque`)
*   **`BankAccount` & `BankTransaction`** : Gestion des comptes bancaires et de l'historique complet des transactions (Lignes Bancaires).
*   **`TransactionCategory` & `CategorizationRule`** : Modèles permettant de catégoriser automatiquement les dépenses et revenus.
*   **`BankReconciliation`** : Table pivot gérant le lettrage entre une transaction bancaire et un document financier (ex: Facture).

### 2. Services & Intégrations API (`app/Services/Banque`)
*   **Open Banking (Powens/Bridge)** : `BridgeApiService` implémente l'intégration API pour synchroniser automatiquement les comptes bancaires de manière sécurisée (OAuth).
*   **Synchronisation et Import** : `StatementImportService` supporte l'import manuel (CSV/QIF).
*   **Moteur d'Intelligence** : 
    *   `TransactionCategorizationService` : Catégorise automatiquement les lignes selon le libellé.
    *   `ReconciliationService` : Moteur de suggestion intelligent qui attribue un score de pertinence pour matcher une transaction avec une Facture, un Ticket de Frais (Carte Corporate, Issue #143) ou une Fiche de Paie (**Appariement RH**, Issues #123, #220).
*   **Trésorerie** : `CashFlowForecastService` (Moteur pour calculer et projeter les flux de trésorerie).
*   **Export SEPA** : Génération de fichiers de virement SEPA pour le paiement groupé des notes de frais validées et **des factures fournisseurs** (Issue #142, #218).

### 3. Observers & Événements (`app/Observers/Banque`)
*   **`BankReconciliationObserver`** : Écoute les lettrages. Dès qu'une facture est intégralement couverte par une ou plusieurs transactions, son statut bascule instantanément sur `PAID` (Payée).

### 4. Interface Utilisateur (Filament)
*   **Panel Filament Banque** : Tableau de bord complet avec visualisation des comptes, indicateurs de trésorerie globale, et widget de suivi des synchronisations.
*   **Prévisionnel de Trésorerie (Forecast)** : Widget graphique interactif superposant le "Solde Confirmé" (basé sur le reste à payer des factures clients et fournisseurs) et le "Solde Prévisionnel" (incluant le lissage des devis signés non encore facturés sur 30 jours).
*   **Refonte du Dashboard Financier** : Intégration avancée de widgets (`laboiteacode/filament-dashboard-widgets`) affichant la variance de la trésorerie, la comparaison temporelle des flux, la répartition sectorielle des dépenses, et l'objectif de rapprochement bancaire.
*   **Factures Fournisseurs (Commerce)** : Bulk Action "Payer par virement (SEPA)" pour générer le XML de paiement et passer le statut des factures en "Paiement en cours".
*   **Tableau de bord "Clôture Mensuelle"** : Page de supervision (`MonthlyClosing`) dédiée à l'expert-comptable listant les anomalies via des widgets séparés : transactions non catégorisées, transactions > 1000€ orphelines, et factures (clients/fournisseurs) marquées payées manuellement sans flux bancaire rattaché.

### 5. Tests
*   Validation solide des intégrations bancaires via PestPHP, incluant la simulation de la synchronisation Open Banking (Bridge API), l'import manuel (StatementImportService) et surtout l'algorithme de lettrage intelligent (ReconciliationService) avec gestion des cas d'usage multiples (doublons, erreurs API).
*   Test unitaire sur la génération SEPA (`SupplierInvoiceSepaTest`) pour garantir le formatage correct du XML selon la norme `.pain`.

### 6. Tâches Planifiées (Background Jobs)
*   **Vérification des Tokens Bridge (DSP2)** : Commande (`CheckBridgeTokensCommand`) planifiée quotidiennement pour vérifier l'expiration des tokens bancaires Open Banking et notifier l'administrateur financier de se réauthentifier 5 jours avant l'échéance. (Issue #217)

## 🚧 Ce qu'il reste à faire
*   Le module est fondamentalement complet. Le **module « Comptabilité » complet** reste à construire (génération des écritures comptables et exports standards pour l'expert-comptable).

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Module "Comptabilité" complet** : (en attente) Générer les écritures comptables depuis les transactions bancaires et produire les exports standards (FEC complet, Sage, Cegid). Seuls des exports partiels existent aujourd'hui : FEC des amortissements (`FecExportService`) et écritures de paie en OD (`AccountingExportService`) — aucun n'est raccordé au module Banque.
