# 🏦 Module Banque & Rapprochement Bancaire

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Structure robuste gérant les Comptes Bancaires (`BankAccount`), les Lignes Bancaires (`BankTransaction`) et le Lettrage (`BankReconciliation`).
*   **Open Banking (Bridge API) :** Intégration en production avec Bridge (Powens). Un parcours d'authentification OAuth permet à l'utilisateur de connecter ses banques. L'application filtre intelligemment les comptes autorisés ou refusés.
*   **Synchronisation Asynchrone :** Récupération de l'historique complet gérée par des Jobs en arrière-plan (`SyncBridgeTransactionsJob`) afin d'éviter les timeouts, optimisée via des insertions en masse (100 lignes par requête SQL).
*   **Automatisation :** Une commande planifiée (`banque:sync-bridge`) tourne chaque jour à 4h00 du matin pour synchroniser silencieusement les nouvelles transactions de tous les utilisateurs.
*   **Services d'Import :** Support natif de l'import de relevés CSV manuels (`StatementImportService`) avec détection de doublons (hash).
*   **Logique de Lettrage (Reconciliation) :** Un moteur de suggestion intelligent (`ReconciliationService`) qui attribue un score de pertinence pour faire matcher une transaction avec une Facture (Client ou Fournisseur).
*   **Automatisation (Observers) :** Un observateur (`BankReconciliationObserver`) écoute les lettrages. Dès qu'une facture est intégralement couverte par les transactions, son statut bascule instantanément sur `PAID` (Payée).
*   **Interface Utilisateur (Filament) :** Un panel dédié "Banque & Rapprochement" incluant :
    *   Un tableau de bord avec indicateurs de Trésorerie Globale, Montant en attente et Graphique (Cash Flow) des entrées/sorties sur 30 jours.
    *   Gestion des comptes bancaires et boutons de synchronisation asynchrone avec notifications systèmes.
    *   Tableau des transactions doté de filtres avancés (Plage de date, Compte, Statut) et bouton "Lettrer" ouvrant une modale de suggestions.
    *   Une "Bulk Action" permettant le **Lettrage de Masse** selon un seuil de confiance minimum choisi par l'utilisateur.
*   **Tests :** Validation logicielle complète avec succès sur la suite PestPHP dédiée au module (mocks stricts de l'API externe, Models, Services).

## 🚧 Ce qu'il reste à faire
*   **Notes de Frais avec OCR :** Développer la brique de notes de frais pour les salariés (scanner le ticket de caisse, extraire le montant/TVA automatiquement, et l'envoyer en validation avant remboursement).

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Prévisionnel de Trésorerie (Forecast) :** Un widget superposant la trésorerie actuelle avec le "Cash Flow projeté" (en incluant les devis validés et les factures fournisseurs à payer dans les 30 prochains jours) pour anticiper un besoin de découvert.
2.  **Appariement des Paies :** Permettre le lettrage automatique des lignes de virement "Salaires" avec les fiches de paie générées par le module RH.
