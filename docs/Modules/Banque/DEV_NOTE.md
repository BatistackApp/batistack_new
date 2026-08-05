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
    *   `ReconciliationService` : Moteur de suggestion intelligent qui attribue un score de pertinence pour matcher une transaction avec une Facture ou un Ticket de Frais (Carte Corporate) (Issue #143).
*   **Trésorerie** : `CashFlowForecastService` (Moteur pour calculer et projeter les flux de trésorerie).
*   **Export SEPA** : Génération de fichiers de virement SEPA pour le paiement groupé des notes de frais validées (Issue #142).

### 3. Observers & Événements (`app/Observers/Banque`)
*   **`BankReconciliationObserver`** : Écoute les lettrages. Dès qu'une facture est intégralement couverte par une ou plusieurs transactions, son statut bascule instantanément sur `PAID` (Payée).

### 4. Interface Utilisateur (Filament)
*   **Panel Filament Banque** : Tableau de bord complet avec visualisation des comptes, indicateurs de trésorerie globale, et widget de suivi des synchronisations.
*   **Prévisionnel de Trésorerie (Forecast)** : Widget graphique interactif superposant le "Solde Confirmé" (basé sur le reste à payer des factures clients et fournisseurs) et le "Solde Prévisionnel" (incluant le lissage des devis signés non encore facturés sur 30 jours).
*   **Refonte du Dashboard Financier** : Intégration avancée de widgets (`laboiteacode/filament-dashboard-widgets`) affichant la variance de la trésorerie, la comparaison temporelle des flux, la répartition sectorielle des dépenses, et l'objectif de rapprochement bancaire.

### 5. Tests
*   Validation solide des intégrations bancaires via PestPHP, incluant la simulation de la synchronisation Open Banking (Bridge API), l'import manuel (StatementImportService) et surtout l'algorithme de lettrage intelligent (ReconciliationService) avec gestion des cas d'usage multiples (doublons, erreurs API).

## 🚧 Ce qu'il reste à faire
*   **Paiement Fournisseurs SEPA** : Étendre l'export SEPA (actuellement utilisé pour les Notes de Frais) au paiement groupé des factures fournisseurs.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Appariement des Paies** : Permettre le lettrage automatique des lignes de virement "Salaires" avec les fiches de paie générées par le module RH.
*   **Module "Comptabilité" complet** : (Mis en attente) Prévoir à terme la création d'un module dédié pour générer les écritures comptables et les exports standards (FEC, Sage, Cegid, etc.) destinés à l'expert-comptable.
