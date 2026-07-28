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
    *   `ReconciliationService` : Moteur de suggestion intelligent qui attribue un score de pertinence pour matcher une transaction avec une Facture.
*   **Trésorerie** : `CashFlowForecastService` (Moteur pour calculer et projeter les flux de trésorerie).

### 3. Observers & Événements (`app/Observers/Banque`)
*   **`BankReconciliationObserver`** : Écoute les lettrages. Dès qu'une facture est intégralement couverte par une ou plusieurs transactions, son statut bascule instantanément sur `PAID` (Payée).

### 4. Interface Utilisateur (Filament)
*   ⚠️ **FRONTEND MANQUANT** : La logique backend est 100% fonctionnelle, mais aucun Panel Filament (`BankResource`, `TransactionResource`, ou Dashboard de Rapprochement) n'est présent dans le répertoire. Le "Tableau de bord avec indicateurs de Trésorerie Globale" et la "Bulk Action de Lettrage" décrits dans les spécifications initiales n'ont pas encore été développés visuellement.

## 🚧 Ce qu'il reste à faire
*   **Création du Panel Filament Banque** : Développer l'UI pour visualiser les comptes, synchroniser via Bridge, lister les transactions, et valider les suggestions de lettrage.
*   **Notes de Frais avec OCR** : Développer la brique de notes de frais pour les salariés (scanner le ticket de caisse, extraire le montant/TVA automatiquement, et l'envoyer en validation avant remboursement).

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Prévisionnel de Trésorerie (Forecast)** : Un widget superposant la trésorerie actuelle avec le "Cash Flow projeté" (en incluant les devis validés et les factures fournisseurs à payer dans les 30 prochains jours) pour anticiper un besoin de découvert.
*   **Appariement des Paies** : Permettre le lettrage automatique des lignes de virement "Salaires" avec les fiches de paie générées par le module RH.
*   **Module "Comptabilité" complet** : (Mis en attente) Prévoir à terme la création d'un module dédié pour générer les écritures comptables et les exports standards (FEC, Sage, Cegid, etc.) destinés à l'expert-comptable.
