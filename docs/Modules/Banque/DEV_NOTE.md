# 🏦 Module Banque & Rapprochement Bancaire

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Structure robuste gérant les Comptes Bancaires (`BankAccount`), les Lignes Bancaires (`BankTransaction`) et le Lettrage (`BankReconciliation`).
*   **Services d'Import :** Support natif de l'import de relevés CSV (`StatementImportService`) avec détection de doublons (hash), et préparation de la synchronisation via API (`BankinApiService`).
*   **Logique de Lettrage (Reconciliation) :** Un moteur de suggestion intelligent (`ReconciliationService`) qui attribue un score de pertinence pour faire matcher une transaction avec une Facture (Client ou Fournisseur). L'algorithme analyse les montants exacts, les références de factures et les noms de tiers présents dans les libellés.
*   **Automatisation (Observers) :** Un observateur (`BankReconciliationObserver`) écoute les lettrages. Dès qu'une facture est intégralement couverte par les transactions, son statut bascule instantanément sur `PAID` (Payée).
*   **Interface Utilisateur (Filament) :** Un panel dédié "Banque & Rapprochement" incluant :
    *   Un tableau de bord avec indicateurs de Trésorerie Globale, Montant en attente et Graphique (Cash Flow) des entrées/sorties sur 30 jours.
    *   Gestion des comptes bancaires et boutons de synchronisation.
    *   Un tableau des transactions avec bouton "Lettrer" ouvrant une modale avec les suggestions pré-calculées par l'algorithme.
*   **Tests :** Validation logicielle complète avec 100% de succès sur la suite PestPHP dédiée au module (Models, Enums, Services, Observers).

## 🚧 Ce qu'il reste à faire
*   **Notes de Frais avec OCR :** Développer la brique de notes de frais pour les salariés (scanner le ticket de caisse, extraire le montant/TVA automatiquement, et l'envoyer en validation avant remboursement).
*   **Lettrage de Masse :** Étendre la vue Filament pour permettre de valider 50 suggestions d'un coup (Bulk Action) afin d'accélérer la tâche du comptable en fin de mois.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Connexion Open Banking (DSP2) :** Relier concrètement le système à Bridge by Bankin' ou Powens en production, afin que les relevés arrivent seuls chaque matin sans aucune intervention humaine.
2.  **Prévisionnel de Trésorerie (Forecast) :** Un widget superposant la trésorerie actuelle avec le "Cash Flow projeté" (en incluant les devis validés et les factures fournisseurs à payer dans les 30 prochains jours) pour anticiper un besoin de découvert.
3.  **Appariement des Paies :** Permettre le lettrage automatique des lignes de virement "Salaires" avec les fiches de paie générées par le module RH.
